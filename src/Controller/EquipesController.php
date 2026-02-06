<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Equipe;
use App\Form\EquipeType;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/equipe')]
final class EquipesController extends AbstractController
{
    #[Route('/', name: 'app_equipes_index', methods: ['GET'])]
    public function index(\Symfony\Component\HttpFoundation\Request $request, EquipeRepository $equipeRepository, \App\Repository\CandidatureRepository $candidatureRepository): Response
    {
        $session = $request->getSession();
        $user = $this->getUser();
        $isManager = false;
        $myTeam = null;

        // 1. Check if user is an ADMIN
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // 2. Check for Manager via Session
        $myTeamId = $session->get('my_team_id');
        if ($myTeamId) {
            $myTeam = $equipeRepository->find($myTeamId);
            $isManager = true;
        }

        // 3. Check for Member (Accepted Candidature) if not already a manager
        if (!$isManager && $user) {
            $userEmail = $user->getUserIdentifier();
            $membership = $candidatureRepository->findOneBy([
                'email' => $userEmail,
                'statut' => 'Accepté'
            ]);
            
            if ($membership) {
                $myTeam = $membership->getEquipe();
                $isManager = $isAdmin; 
            }
        }

        // Si l'utilisateur a une équipe et que c'est une requête de type "explorer", 
        // ou si c'est la vue liste explicite, on affiche la liste.
        // Sinon, on pourrait rediriger vers show comme avant, mais le USER a demandé que 
        // l'interface de equipes/index soit la même que home (qui est une liste).
        
        // Affiche la page d'accueil principale (identique à l'accueil)
        return $this->render('equipes/index.html.twig', [
            'featuredTeams' => $equipeRepository->findBy([], ['id' => 'DESC'], 4),
            'myTeam' => $myTeam,
            'isManager' => $isManager || $isAdmin
        ]);
    }

    #[Route('/api/search', name: 'app_equipes_api_search', methods: ['GET'])]
    public function apiSearch(Request $request, EquipeRepository $equipeRepository): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $term = $request->query->get('q', '');
        $equipes = $equipeRepository->searchByName($term);
        
        $data = [];
        foreach ($equipes as $equipe) {
            $data[] = [
                'id' => $equipe->getId(),
                'name' => $equipe->getNomEquipe(),
                'description' => substr($equipe->getDescription(), 0, 100) . '...',
                'logo' => $equipe->getLogo(),
                'rank' => $equipe->getClassement() ?: 'Non classé',
                'tag' => $equipe->getTag(),
                'creationDate' => $equipe->getDateCreation() ? $equipe->getDateCreation()->format('d/m/Y') : 'N/A',
                'region' => $equipe->getRegion() ?: 'EUW',
            ];
        }
        
        return $this->json($data);
    }
    
    #[Route('/api/create', name: 'app_equipes_api_create', methods: ['POST'])]
    public function apiCreate(Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            $equipe = new Equipe();
            
            // Récupérer les données du formulaire
            $nomEquipe = trim($request->request->get('nomEquipe'));
            $description = trim($request->request->get('description'));
            $dateCreation = $request->request->get('dateCreation');
            $classement = $request->request->get('classement');
            $tag = strtoupper(trim($request->request->get('tag')));
            $region = $request->request->get('region');
            $maxMembers = (int)$request->request->get('maxMembers');
            $isPrivate = $request->request->get('isPrivate') === 'on' || $request->request->get('isPrivate') === '1';
            
            $logo = $request->request->get('logo');
            $logoFile = $request->files->get('logo');
            $equipeFiles = $request->files->all('equipe');

            if ((!$logoFile || $logoFile === null) && is_array($equipeFiles) && array_key_exists('logo', $equipeFiles)) {
                $logoFile = $equipeFiles['logo'];
            }
            
            // CONTROLE DE SAISIE (BACKEND ONLY)
            $errors = [];

            if (empty($nomEquipe) || strlen($nomEquipe) < 3) {
                $errors[] = "Le nom de l'équipe doit comporter au moins 3 caractères.";
            }

            if (empty($tag) || strlen($tag) < 2 || strlen($tag) > 6) {
                $errors[] = "Le Tag de l'équipe doit comporter entre 2 et 6 caractères.";
            }

            if (empty($classement)) {
                $errors[] = "Veuillez sélectionner un classement pour votre équipe.";
            }

            if ($maxMembers < 2 || $maxMembers > 50) {
                $errors[] = "Le nombre maximum de membres doit être compris entre 2 et 50.";
            }

            // Description par défaut si vide
            if (empty($description)) {
                $description = 'Aucune description';
            }

            if (!empty($errors)) {
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'success' => false,
                    'message' => 'Veuillez corriger les erreurs dans le formulaire.',
                    'errors' => $errors
                ], 400);
            }
            
            // Remplir l'entité
            $equipe->setNomEquipe($nomEquipe);
            $equipe->setDescription($description);
            $equipe->setDateCreation(new \DateTime($dateCreation ?: 'now'));
            $equipe->setClassement($classement);
            $equipe->setTag($tag);
            $equipe->setRegion($region ?: null);
            $equipe->setIsPrivate($isPrivate);
            $equipe->setMaxMembers($maxMembers);

            // Handle logo upload (file) or base64 data URL
            $targetDir = $this->getParameter('kernel.project_dir').'/public/uploads/equipes';
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            if ($logoFile) {
                $newFilename = uniqid().'.'.$logoFile->guessExtension();
                $logoFile->move($targetDir, $newFilename);
                $equipe->setLogo($newFilename);
            } elseif (!empty($logo) && str_starts_with($logo, 'data:')) {
                if (preg_match('/^data:(image\/[^;]+);base64,(.*)$/', $logo, $matches)) {
                    $mime = $matches[1];
                    $data = base64_decode($matches[2]);
                    $ext = 'png';
                    switch ($mime) {
                        case 'image/jpeg': $ext = 'jpg'; break;
                        case 'image/gif': $ext = 'gif'; break;
                        case 'image/png':
                        default: $ext = 'png'; break;
                    }
                    $newFilename = uniqid().'.'.$ext;
                    file_put_contents($targetDir.DIRECTORY_SEPARATOR.$newFilename, $data);
                    $equipe->setLogo($newFilename);
                }
            } else {
                $equipe->setLogo(null);
            }
            
            // Persister
            $entityManager->persist($equipe);
            $entityManager->flush();

            // Store created team id in session ONLY for non-admins (Managers)
            try {
                $session = $request->getSession();
                if ($session && !$this->isGranted('ROLE_ADMIN')) {
                    $session->set('my_team_id', $equipe->getId());
                }
            } catch (\Exception $e) {
                // ignore session errors
            }
            // Add flash message (useful for non-AJAX flows)
            try {
                $this->addFlash('success', 'Équipe créée et sélectionnée comme votre équipe.');
            } catch (\Exception $e) {
                // ignore flash errors
            }
            
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success' => true,
                'message' => 'Équipe créée avec succès',
                'id' => $equipe->getId()
            ]);
            
        } catch (\Exception $e) {
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/new', name: 'app_equipes_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $equipe = new Equipe();
        $form = $this->createForm(EquipeType::class, $equipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($equipe);
            $entityManager->flush();

            // Store created team id in session ONLY for non-admins (Managers)
            try {
                if (!$this->isGranted('ROLE_ADMIN')) {
                    $request->getSession()->set('my_team_id', $equipe->getId());
                }
            } catch (\Exception $e) {
                // ignore session problems
            }

            // Add flash message so user sees confirmation after redirect
            $this->addFlash('success', 'Équipe créée et sélectionnée comme votre équipe.');

            // Si c'est une requête AJAX, retourner JSON
            if ($request->isXmlHttpRequest()) {
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'success' => true,
                    'message' => 'Équipe créée avec succès',
                    'id' => $equipe->getId()
                ]);
            }

            // Redirect Admin back to dashboard, others to show page
            if ($this->isGranted('ROLE_ADMIN') || str_contains($request->headers->get('referer', ''), '/admin')) {
                return $this->redirectToRoute('admin_equipes');
            }

            return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()], Response::HTTP_SEE_OTHER);
        }

        // Si c'est une requête AJAX avec erreur de validation
        if ($request->isXmlHttpRequest() && $form->isSubmitted()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success' => false,
                'errors' => $errors
            ], 400);
        }

        return $this->render('equipes/new.html.twig', [
            'equipe' => $equipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_equipes_show', methods: ['GET'])]
    public function show(Equipe $equipe, \App\Repository\CandidatureRepository $candidatureRepository, Request $request): Response
    {
        $user = $this->getUser();
        $isMember = false;
        $isManager = false;

        // Check Manager Status (Session based OR Admin)
        $session = $request->getSession();
        if (($session && $session->get('my_team_id') == $equipe->getId()) || $this->isGranted('ROLE_ADMIN')) {
            $isManager = true;
        }

        // Check Member Status (Accepted Candidature)
        if ($user) {
            $userEmail = $user->getUserIdentifier(); // Assuming email is the identifier
            $membership = $candidatureRepository->findOneBy([
                'equipe' => $equipe,
                'email' => $userEmail,
                'statut' => 'Accepté'
            ]);
            
            if ($membership) {
                $isMember = true;
            }
        }

        return $this->render('equipes/show.html.twig', [
            'equipe' => $equipe,
            'isManager' => $isManager,
            'isMember' => $isMember
        ]);
    }

    #[Route('/{id}/postuler', name: 'app_equipes_apply', methods: ['GET', 'POST'])]
    public function postuler(Request $request, Equipe $equipe, EntityManagerInterface $entityManager, \App\Repository\CandidatureRepository $candidatureRepository): Response
    {
        $user = $this->getUser();
        $session = $request->getSession();
        
        // Check if already manager or member
        $isManager = ($session && $session->get('my_team_id') == $equipe->getId()) || $this->isGranted('ROLE_ADMIN');
        $isMember = false;
        if ($user) {
            $membership = $candidatureRepository->findOneBy([
                'equipe' => $equipe,
                'email' => $user->getUserIdentifier(),
                'statut' => 'Accepté'
            ]);
            if ($membership) $isMember = true;
        }

        if ($isManager) {
            $this->addFlash('info', 'Vous êtes déjà le manager de cette équipe.');
            return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()]);
        }

        if ($isMember) {
            $this->addFlash('info', 'Vous êtes déjà membre de cette équipe.');
            return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()]);
        }

        if ($request->isMethod('POST')) {
            $pseudo = $request->request->get('pseudo');
            $participantEmail = $request->request->get('email');
            $niveau = $request->request->get('niveau');
            $reason = $request->request->get('reason');
            $playStyle = $request->request->get('playStyle');
            $motivation = $request->request->get('motivation', 'Candidature spontanée');

            $errors = [];

            // CONTROLE DE SAISIE (BACKEND ONLY)
            if (empty($pseudo) || strlen($pseudo) < 3) {
                $errors[] = "Le pseudo est requis et doit faire au moins 3 caractères.";
            }

            if (empty($participantEmail) || !filter_var($participantEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Un email valide est requis pour vous recontacter.";
            }

            if (empty($niveau)) {
                $errors[] = "Veuillez sélectionner votre niveau de jeu.";
            }

            if (empty($reason) || strlen($reason) < 10) {
                $errors[] = "Veuillez expliquer pourquoi vous voulez rejoindre l'équipe (min. 10 caractères).";
            }

            if (empty($playStyle) || strlen($playStyle) < 5) {
                $errors[] = "Veuillez décrire votre style de jeu (min. 5 caractères).";
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('equipes/apply.html.twig', [
                    'equipe' => $equipe,
                    'formData' => $request->request->all()
                ]);
            }

            $candidature = new Candidature();
            $candidature->setEquipe($equipe);
            $candidature->setPseudo($pseudo);
            $candidature->setEmail($participantEmail);
            $candidature->setNiveau($niveau);
            $candidature->setMotivation($motivation);
            $candidature->setReason($reason);
            $candidature->setPlayStyle($playStyle);
            
            $entityManager->persist($candidature);
            $entityManager->flush();

            return $this->render('equipes/apply_success.html.twig', [
                'equipe' => $equipe
            ]);
        }

        return $this->render('equipes/apply.html.twig', [
            'equipe' => $equipe,
        ]);
    }

    #[Route('/{id}/manage', name: 'app_equipes_manage', methods: ['GET'])]
    public function manage(Equipe $equipe): Response
    {
        return $this->render('equipes/manage.html.twig', [
            'equipe' => $equipe,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_equipes_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Equipe $equipe, EntityManagerInterface $entityManager): Response
    {
        $from = $request->query->get('from');
        // SECURITY CHECK: Only Team Manager OR Admin can edit
        $session = $request->getSession();
        $isManager = $session && $session->get('my_team_id') == $equipe->getId();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isManager && !$isAdmin) {
            $this->addFlash('error', 'Accès refusé : Vous devez être connecté en tant qu\'Administrateur ou être le Manager de l\'équipe pour modifier.');
            return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()]);
        }

        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire personnalisé
            // Essayer d'abord le format array (equipe[nomEquipe])
            $data = $request->request->all('equipe');
            
            // Si le format array ne fonctionne pas, essayer le format plat
            if (empty($data) || !is_array($data)) {
                $data = [
                    'nomEquipe' => $request->request->get('nomEquipe'),
                    'description' => $request->request->get('description'),
                    'classement' => $request->request->get('classement'),
                    'tag' => $request->request->get('tag'),
                    'dateCreation' => $request->request->get('dateCreation'),
                    'region' => $request->request->get('region'),
                ];
            }
            
            if (!empty($data['nomEquipe'])) {
                $equipe->setNomEquipe($data['nomEquipe']);
            }
            if (!empty($data['description'])) {
                $equipe->setDescription($data['description']);
            }
            if (!empty($data['classement'])) {
                $equipe->setClassement($data['classement']);
            }
            if (!empty($data['tag'])) {
                $equipe->setTag($data['tag']);
            }
            if (array_key_exists('region', $data) && $data['region'] !== null) {
                $equipe->setRegion($data['region']);
            }
            if (!empty($data['dateCreation'])) {
                $equipe->setDateCreation(new \DateTime($data['dateCreation']));
            }
            
            // Gérer le logo uploadé (supporte input name equipe[logo] ou logo)
            $logoFile = $request->files->get('logo');
            $equipeFiles = $request->files->all('equipe');
            if ((!$logoFile || $logoFile === null) && is_array($equipeFiles) && array_key_exists('logo', $equipeFiles)) {
                $logoFile = $equipeFiles['logo'];
            }

            $targetDir = $this->getParameter('kernel.project_dir').'/public/uploads/equipes';
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            if ($logoFile) {
                $newFilename = uniqid().'.'.$logoFile->guessExtension();
                $logoFile->move($targetDir, $newFilename);
                $equipe->setLogo($newFilename);
            } else {
                // also support base64 string sent in form field (e.g. equipe[logo])
                $equipeData = $request->request->all('equipe');
                $logoField = is_array($equipeData) && array_key_exists('logo', $equipeData) ? $equipeData['logo'] : $request->request->get('logo');
                if (!empty($logoField) && str_starts_with($logoField, 'data:')) {
                    if (preg_match('/^data:(image\/[^;]+);base64,(.*)$/', $logoField, $matches)) {
                        $mime = $matches[1];
                        $data = base64_decode($matches[2]);
                        $ext = 'png';
                        switch ($mime) {
                            case 'image/jpeg': $ext = 'jpg'; break;
                            case 'image/gif': $ext = 'gif'; break;
                            case 'image/png':
                            default: $ext = 'png'; break;
                        }
                        $newFilename = uniqid().'.'.$ext;
                        file_put_contents($targetDir.DIRECTORY_SEPARATOR.$newFilename, $data);
                        $equipe->setLogo($newFilename);
                    }
                }
            }
            
            // Persister explicitement les modifications
            $entityManager->persist($equipe);
            $entityManager->flush();
            
            $this->addFlash('success', 'Équipe "' . $equipe->getNomEquipe() . '" modifiée avec succès !');

            // Redirect Admin back to dashboard, others to show page
            if ($from === 'admin' || $this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_equipes');
            }

            return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipes/edit.html.twig', [
            'equipe' => $equipe,
            'from' => $from,
        ]);
    }

    #[Route('/{id}', name: 'app_equipes_delete', methods: ['POST'])]
    public function delete(Request $request, Equipe $equipe, EntityManagerInterface $entityManager): Response
    {
        // SECURITY CHECK: Only Team Manager OR Admin can delete
        $session = $request->getSession();
        $isManager = $session && $session->get('my_team_id') == $equipe->getId();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isManager && !$isAdmin) {
            $this->addFlash('error', 'Accès refusé : Seul le Manager ou l\'Administrateur peut supprimer cette équipe.');
            return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()]);
        }

        // Récupérer le token CSRF depuis le body (fallback vers getPayload si présent)
        $token = $request->request->get('_token');
        if (!$token && method_exists($request, 'getPayload')) {
            try {
                $token = $request->getPayload()->getString('_token');
            } catch (\Throwable $e) {
                $token = null;
            }
        }

        if ($this->isCsrfTokenValid('delete'.$equipe->getId(), $token)) {
            $entityManager->remove($equipe);
            $entityManager->flush();

            // Si la session contenait cette équipe comme "my_team_id", la retirer
            try {
                $session = $request->getSession();
                if ($session && $session->get('my_team_id') == $equipe->getId()) {
                    $session->remove('my_team_id');
                }
            } catch (\Exception $e) {
                // ignore session errors
            }

            $this->addFlash('success', 'Équipe supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Échec de la suppression : Jeton CSRF invalide ou expiré (token: ' . substr($token, 0, 8) . '...).');
        }

        // Redirect Admin back to dashboard, others to index (which redirects to show)
        if ($this->isGranted('ROLE_ADMIN') || str_contains($request->headers->get('referer', ''), '/admin')) {
            return $this->redirectToRoute('admin_equipes');
        }

        return $this->redirectToRoute('app_equipes_index', [], Response::HTTP_SEE_OTHER);
    }
}
