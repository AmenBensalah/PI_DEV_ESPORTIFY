<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Equipe;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/equipe')]
final class EquipesController extends AbstractController
{
    #[Route('/db-debug-fix', name: 'app_equipes_db_fix', methods: ['GET'])]
    public function debugFix(EntityManagerInterface $entityManager): Response
    {
        $conn = $entityManager->getConnection();
        $output = "";
        
        // 1. Add manager_id
        try {
            $conn->executeStatement("ALTER TABLE equipe ADD manager_id INT DEFAULT NULL");
            $output .= "Colonne 'manager_id' ajoutée. ";
        } catch (\Exception $e) {
            $output .= "Note: La colonne 'manager_id' existe déjà probablement (" . $e->getMessage() . "). ";
        }
        
        // 2. Add Foreign Key
        try {
            $conn->executeStatement("ALTER TABLE equipe ADD CONSTRAINT FK_MANAGER_USER FOREIGN KEY (manager_id) REFERENCES user (id)");
            $output .= "Contrainte de clé étrangère ajoutée. ";
        } catch (\Exception $e) {
            $output .= "Note: La contrainte existe déjà probablement. ";
        }

        // 3. Clear Cache if possible
        try {
            $entityManager->getConfiguration()->getMetadataCache()->clear();
            $output .= "Cache Doctrine vidé.";
        } catch (\Exception $e) {
            $output .= "Impossible de vider le cache via code, mais ce n'est pas grave.";
        }

        return new Response($output . "<br><br><b>Veuillez retourner sur votre application.</b> Si l'erreur persiste, videz manuellement le dossier var/cache/dev.");
    }

    #[Route('/', name: 'app_equipes_index', methods: ['GET'])]
    public function index(\Symfony\Component\HttpFoundation\Request $request, EquipeRepository $equipeRepository, \App\Repository\CandidatureRepository $candidatureRepository): Response
    {
        $session = $request->getSession();
        $user = $this->getUser();

        // 1. Check if user is an ADMIN
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        if ($isAdmin) {
            return $this->redirectToRoute('admin_equipes');
        }

        // 2. For Managers/Players, we show the landing page (index)
        $myTeamId = $session->get('my_team_id');
        $myTeam = null;
        if ($myTeamId) {
            $myTeam = $equipeRepository->find($myTeamId);
        }

        // 3. Detect membership (accepted candidature)
        $memberTeam = null;
        $isMember = false;
        if ($user) {
            $membership = $candidatureRepository->findOneBy([
                'user' => $user,
                'statut' => 'Accepté'
            ]);
            if ($membership && $membership->getEquipe()) {
                $memberTeam = $membership->getEquipe();
                $isMember = true;
            }
        }

        // 4. Calculate Manager status for the view
        $hasManagerRole = $this->isGranted('ROLE_MANAGER') || $this->isGranted('ROLE_ADMIN');
        $isManager = $hasManagerRole && $myTeam && $myTeamId == $myTeam->getId();

        $canCreateTeam = $hasManagerRole && !$myTeam;
        $canRequestManager = !$hasManagerRole && !$isMember;
        $canJoinTeam = !$hasManagerRole && !$isMember;

        return $this->render('equipes/index.html.twig', [
            'featuredTeams' => $equipeRepository->findActiveLatest(4),
            'myTeam' => $myTeam,
            'isManager' => $isManager,
            'isMember' => $isMember,
            'memberTeam' => $memberTeam,
            'canCreateTeam' => $canCreateTeam,
            'canRequestManager' => $canRequestManager,
            'canJoinTeam' => $canJoinTeam,
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
            if (!$this->isGranted('ROLE_MANAGER') && !$this->isGranted('ROLE_ADMIN')) {
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'success' => false,
                    'message' => 'Accès refusé : vous devez être manager pour créer une équipe.'
                ], 403);
            }

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
            $discordInviteUrlRaw = $request->request->get('discordInviteUrl');
            $discordInviteUrl = $this->normalizeDiscordInvite($discordInviteUrlRaw);
            
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
            if (!empty($discordInviteUrlRaw) && $discordInviteUrl === null) {
                $errors[] = "Lien Discord invalide. Utilisez un lien discord.gg ou discord.com/invite/...";
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
            $equipe->setDiscordInviteUrl($discordInviteUrl);

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
            
            $equipe->setManager($this->getUser());
            
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
                'id' => $equipe->getId(),
                'redirect' => $this->generateUrl('app_equipes_show', ['id' => $equipe->getId()]),
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
        if (!$this->isGranted('ROLE_MANAGER') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous devez être manager pour créer une équipe.');
            return $this->redirectToRoute('app_become_manager');
        }

        $equipe = new Equipe();

        if ($request->isMethod('POST')) {
            $nomEquipe = trim((string) $request->request->get('nomEquipe'));
            $description = trim((string) $request->request->get('description'));
            $dateCreation = $request->request->get('dateCreation');
            $classement = $request->request->get('classement');
            $tag = strtoupper(trim((string) $request->request->get('tag')));
            $region = $request->request->get('region');
            $maxMembers = (int) $request->request->get('maxMembers');
            $isPrivate = $request->request->get('isPrivate') === 'on' || $request->request->get('isPrivate') === '1';
            $discordInviteUrlRaw = $request->request->get('discordInviteUrl');
            $discordInviteUrl = $this->normalizeDiscordInvite($discordInviteUrlRaw);

            $logoFile = $request->files->get('logo');

            $errors = [];
            if ($nomEquipe === '' || mb_strlen($nomEquipe) < 3) {
                $errors[] = "Le nom de l'équipe doit comporter au moins 3 caractères.";
            }
            if ($tag === '' || mb_strlen($tag) < 2 || mb_strlen($tag) > 6) {
                $errors[] = "Le Tag de l'équipe doit comporter entre 2 et 6 caractères.";
            }
            if (empty($classement)) {
                $errors[] = "Veuillez sélectionner un classement pour votre équipe.";
            }
            if ($maxMembers < 2 || $maxMembers > 50) {
                $errors[] = "Le nombre maximum de membres doit être compris entre 2 et 50.";
            }
            if (!empty($discordInviteUrlRaw) && $discordInviteUrl === null) {
                $errors[] = "Lien Discord invalide. Utilisez un lien discord.gg ou discord.com/invite/...";
            }

            if ($description === '') {
                $description = 'Aucune description';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('equipes/new.html.twig', [
                    'equipe' => $equipe,
                ]);
            }

            $equipe->setNomEquipe($nomEquipe);
            $equipe->setDescription($description);
            $equipe->setDateCreation(new \DateTime($dateCreation ?: 'now'));
            $equipe->setClassement($classement);
            $equipe->setTag($tag);
            $equipe->setRegion($region ?: null);
            $equipe->setIsPrivate($isPrivate);
            $equipe->setMaxMembers($maxMembers);
            $equipe->setDiscordInviteUrl($discordInviteUrl);

            $targetDir = $this->getParameter('kernel.project_dir').'/public/uploads/equipes';
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            if ($logoFile) {
                $newFilename = uniqid().'.'.$logoFile->guessExtension();
                $logoFile->move($targetDir, $newFilename);
                $equipe->setLogo($newFilename);
            } else {
                $equipe->setLogo(null);
            }

            $equipe->setManager($this->getUser());
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

            // Redirect Admin back to dashboard
            if ($this->isGranted('ROLE_ADMIN') || str_contains($request->headers->get('referer', ''), '/admin')) {
                return $this->redirectToRoute('admin_equipes');
            }
            return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipes/new.html.twig', [
            'equipe' => $equipe,
        ]);
    }

    #[Route('/{id}', name: 'app_equipes_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        Equipe $equipe,
        \App\Repository\CandidatureRepository $candidatureRepository,
        Request $request,
        \App\Repository\UserRepository $userRepository,
        \App\Service\TeamBalanceService $teamBalanceService,
        \App\Service\TeamPerformanceService $teamPerformanceService,
        \App\Service\TeamLevelStatsService $teamLevelStatsService,
        \App\Service\TeamRankAiService $teamRankAiService
    ): Response
    {
        if (!$equipe->isActive() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Cette équipe est suspendue.');
            return $this->redirectToRoute('app_equipes_index');
        }

        $user = $this->getUser();
        $isMember = false;
        $isManager = false;

        // Check Manager Status (Session based AND Role based OR Admin)
        $session = $request->getSession();
        $hasManagerRole = $this->isGranted('ROLE_MANAGER');
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // To be a manager, you must have the ROLE and be in your own team session, OR be Admin
        if ($isAdmin || ($hasManagerRole && $session && $session->get('my_team_id') == $equipe->getId())) {
            $isManager = true;
        }

        // Check Member Status (Accepted Candidature)
        $membershipAny = null;
        if ($user) {
            $membershipAny = $candidatureRepository->findOneBy([
                'user' => $user,
                'statut' => 'Accepté'
            ]);

            if ($membershipAny && $membershipAny->getEquipe() && $membershipAny->getEquipe()->getId() === $equipe->getId()) {
                $isMember = true;
            }
        }

        $allCandidatures = $candidatureRepository->findBy(
            ['equipe' => $equipe],
            ['dateCandidature' => 'DESC']
        );

        // Fetch dynamic members (Accepted candidatures)
        $membersCandidatures = $candidatureRepository->findBy([
            'equipe' => $equipe,
            'statut' => 'Accepté'
        ]);

        $members = [];
        foreach ($membersCandidatures as $cand) {
            $memberUser = $cand->getUser();
            $members[] = [
                'id' => $memberUser ? $memberUser->getId() : null,
                'nom' => $memberUser ? $memberUser->getNom() : 'Utilisateur',
                'pseudo' => $memberUser ? $memberUser->getPseudo() : 'Joueur',
                'email' => $memberUser ? $memberUser->getEmail() : 'N/A',
                'date' => $cand->getDateCandidature() ? $cand->getDateCandidature()->format('d/m/Y') : null
            ];
        }

        $canJoinTeam = !$isAdmin && !$hasManagerRole && !$isMember && !$membershipAny;

        $teamBalance = $teamBalanceService->analyze($equipe, $allCandidatures);
        $teamPerformance = $teamPerformanceService->analyze($equipe);
        $teamLevelStats = $teamLevelStatsService->analyze($equipe, $allCandidatures);
        $membersCount = count($members) + ($equipe->getManager() ? 1 : 0);
        $teamAiRank = $teamRankAiService->predict($equipe, $teamBalance, $teamPerformance, $teamLevelStats, $membersCount);

        return $this->render('equipes/show.html.twig', [
            'equipe' => $equipe,
            'isManager' => $isManager,
            'isMember' => $isMember,
            'members' => $members,
            'canJoinTeam' => $canJoinTeam,
            'teamBalance' => $teamBalance,
            'teamPerformance' => $teamPerformance,
            'teamLevelStats' => $teamLevelStats,
            'teamAiRank' => $teamAiRank,
        ]);
    }

    #[Route('/{id}/postuler', name: 'app_equipes_apply', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function postuler(
        Request $request,
        Equipe $equipe,
        EntityManagerInterface $entityManager,
        \App\Repository\CandidatureRepository $candidatureRepository,
        \App\Service\ReasonQualityAiService $reasonQualityAiService
    ): Response
    {
        if (!$equipe->isActive() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Cette équipe est suspendue.');
            return $this->redirectToRoute('app_equipes_index');
        }

        $user = $this->getUser();
        $session = $request->getSession();
        
        // Check if already manager or member
        $isManager = ($session && $session->get('my_team_id') == $equipe->getId()) || $this->isGranted('ROLE_ADMIN');
        $isMember = false;
        if ($user) {
            $membership = $candidatureRepository->findOneBy([
                'equipe' => $equipe,
                'user' => $user,
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

        // Global Restriction: If player is ALREADY member of ANY team
        if ($user && $this->isGranted('ROLE_JOUEUR')) {
            $existingMembership = $candidatureRepository->findOneBy([
                'user' => $user,
                'statut' => 'Accepté'
            ]);
            if ($existingMembership) {
                $this->addFlash('error', 'Vous appartenez déjà à l\'équipe ' . $existingMembership->getEquipe()->getNomEquipe());
                return $this->redirectToRoute('app_equipes_show', ['id' => $existingMembership->getEquipe()->getId()]);
            }
        }

        if ($request->isMethod('POST')) {
            $niveau = $request->request->get('niveau');
            $reason = $request->request->get('reason');
            $playStyle = $request->request->get('playStyle');
            $region = $request->request->get('region');
            $disponibilite = $request->request->get('disponibilite');
            $motivation = $request->request->get('motivation', 'Candidature spontanée');

            $errors = [];

            // CONTROLE DE SAISIE (BACKEND ONLY)
            if (empty($niveau)) {
                $errors[] = "Veuillez sélectionner votre niveau de jeu.";
            }

            if (empty($reason) || strlen($reason) < 10) {
                $errors[] = "Veuillez expliquer pourquoi vous voulez rejoindre l'équipe (min. 10 caractères).";
            }

            if (empty($playStyle) || strlen($playStyle) < 5) {
                $errors[] = "Veuillez décrire votre style de jeu (min. 5 caractères).";
            }

            if (empty($region)) {
                $errors[] = "Veuillez sélectionner votre région.";
            }

            if (empty($disponibilite)) {
                $errors[] = "Veuillez sélectionner votre disponibilité.";
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
            $candidature->setUser($user);
            $candidature->setNiveau($niveau);
            $candidature->setMotivation($motivation);
            $candidature->setReason($reason);
            $candidature->setPlayStyle($playStyle);
            $candidature->setRegion($region);
            $candidature->setDisponibilite($disponibilite);
            $reasonAi = $reasonQualityAiService->analyze((string) $reason);
            $candidature->setReasonAiScore((int) $reasonAi['score']);
            $candidature->setReasonAiLabel((string) $reasonAi['label']);
            
            $entityManager->persist($candidature);
            $entityManager->flush();

            $this->addFlash('success', 'Votre candidature a bien été envoyée.');
            return $this->redirectToRoute('fil_home');
        }

        return $this->render('equipes/apply.html.twig', [
            'equipe' => $equipe,
        ]);
    }

    #[Route('/candidature/analyze-reason', name: 'app_equipes_reason_analyze', methods: ['POST'])]
    public function analyzeReason(
        Request $request,
        \App\Service\ReasonQualityAiService $reasonQualityAiService
    ): \Symfony\Component\HttpFoundation\JsonResponse {
        $reason = trim((string) $request->request->get('reason', ''));
        if ($reason === '') {
            return $this->json([
                'success' => false,
                'message' => 'Texte vide',
            ], 400);
        }

        $result = $reasonQualityAiService->analyze($reason);
        return $this->json([
            'success' => true,
            'score' => (int) $result['score'],
            'label' => (string) $result['label'],
            'analysis' => (string) $result['analysis'],
        ]);
    }

    #[Route('/{id}/quitter', name: 'app_equipes_leave', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function quitter(Equipe $equipe, Request $request, \App\Repository\CandidatureRepository $candidatureRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // SECURITY CHECK: Must be an accepted member
        $membership = $candidatureRepository->findOneBy([
            'equipe' => $equipe,
            'user' => $user,
            'statut' => 'Accepté'
        ]);

        if (!$membership) {
            $this->addFlash('error', 'Action impossible : vous n\'êtes pas membre de cette équipe.');
            return $this->redirectToRoute('app_equipes_index');
        }

        // Managers cannot leave their own group this way (they must delete or transfer)
        $session = $request->getSession();
        if ($this->isGranted('ROLE_MANAGER') && $session->get('my_team_id') == $equipe->getId()) {
            $this->addFlash('warning', 'En tant que Manager, vous ne pouvez pas quitter l\'équipe. Supprimez-la ou transférez vos droits d\'abord.');
            return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()]);
        }

        // Delete the candidature to "leave"
        $entityManager->remove($membership);
        $entityManager->flush();

        // Clear the session variable if it matches the team being left
        if ($session->get('my_team_id') == $equipe->getId()) {
            $session->remove('my_team_id');
        }

        $this->addFlash('success', 'Vous avez quitté l\'équipe ' . $equipe->getNomEquipe() . '.');
        return $this->redirectToRoute('fil_home');
    }

    #[Route('/{id}/manage', name: 'app_equipes_manage', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function manage(
        Equipe $equipe,
        \App\Repository\CandidatureRepository $candidatureRepository,
        \App\Service\TeamBalanceService $teamBalanceService,
        \App\Service\TeamPerformanceService $teamPerformanceService,
        \App\Service\TeamLevelStatsService $teamLevelStatsService
    ): Response
    {
        $candidatures = $candidatureRepository->findBy(['equipe' => $equipe], ['dateCandidature' => 'DESC']);
        $balance = $teamBalanceService->analyze($equipe, $candidatures);
        $performance = $teamPerformanceService->analyze($equipe);
        $levelStats = $teamLevelStatsService->analyze($equipe, $candidatures);

        return $this->render('equipes/manage.html.twig', [
            'equipe' => $equipe,
            'teamBalance' => $balance,
            'teamPerformance' => $performance,
            'teamLevelStats' => $levelStats,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_equipes_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Equipe $equipe, EntityManagerInterface $entityManager): Response
    {
        $from = $request->query->get('from');
        // SECURITY CHECK: Only Team Manager (ROLE_MANAGER + session) OR Admin can edit
        $session = $request->getSession();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isManager = $this->isGranted('ROLE_MANAGER') && $session && $session->get('my_team_id') == $equipe->getId();

        if (!$isManager && !$isAdmin) {
            $this->addFlash('error', 'Accès refusé : Seuls les Managers de l\'équipe ou les Administrateurs peuvent modifier.');
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
                    'discordInviteUrl' => $request->request->get('discordInviteUrl'),
                ];
            }

            $errors = [];
            $nomEquipe = trim((string) ($data['nomEquipe'] ?? ''));
            $description = trim((string) ($data['description'] ?? ''));
            $tag = strtoupper(trim((string) ($data['tag'] ?? '')));
            $classement = $data['classement'] ?? null;
            $discordInviteUrlRaw = $data['discordInviteUrl'] ?? null;
            $discordInviteUrl = $this->normalizeDiscordInvite($discordInviteUrlRaw);

            if ($nomEquipe === '' || mb_strlen($nomEquipe) < 3) {
                $errors[] = "Le nom de l'équipe doit comporter au moins 3 caractères.";
            }

            if ($tag !== '' && (mb_strlen($tag) < 2 || mb_strlen($tag) > 6)) {
                $errors[] = "Le Tag de l'équipe doit comporter entre 2 et 6 caractères.";
            }

            if ($description !== '' && mb_strlen($description) < 5) {
                $errors[] = "La description doit comporter au moins 5 caractères.";
            }
            if (!empty($discordInviteUrlRaw) && $discordInviteUrl === null) {
                $errors[] = "Lien Discord invalide. Utilisez un lien discord.gg ou discord.com/invite/...";
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('equipes/edit.html.twig', [
                    'equipe' => $equipe,
                    'from' => $from,
                ]);
            }
            
            if ($nomEquipe !== '') {
                $equipe->setNomEquipe($nomEquipe);
            }
            if ($description !== '') {
                $equipe->setDescription($description);
            }
            if (!empty($classement)) {
                $equipe->setClassement($classement);
            }
            if ($tag !== '') {
                $equipe->setTag($tag);
            }
            if (array_key_exists('region', $data) && $data['region'] !== null) {
                $equipe->setRegion($data['region']);
            }
            $equipe->setDiscordInviteUrl($discordInviteUrl);
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

    #[Route('/{id}', name: 'app_equipes_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Equipe $equipe, EntityManagerInterface $entityManager): Response
    {
        // SECURITY CHECK: Only Team Manager (ROLE_MANAGER + session) OR Admin can delete
        $session = $request->getSession();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isManager = $this->isGranted('ROLE_MANAGER') && $session && $session->get('my_team_id') == $equipe->getId();

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

    #[Route('/{id}/add-member', name: 'app_equipes_add_member', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addMember(Request $request, Equipe $equipe, EntityManagerInterface $entityManager, \App\Repository\UserRepository $userRepository, \App\Repository\CandidatureRepository $candidatureRepository): Response
    {
        // SECURITY CHECK
        $session = $request->getSession();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isManager = $this->isGranted('ROLE_MANAGER') && $session && $session->get('my_team_id') == $equipe->getId();

        if (!$isManager && !$isAdmin) {
            $this->addFlash('error', 'Accès refusé.');
            return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()]);
        }

        $identifier = trim($request->request->get('identifier'));
        if (empty($identifier)) {
            $this->addFlash('error', 'Veuillez entrer un pseudo ou un email.');
            return $this->redirectToRoute('app_equipes_manage', ['id' => $equipe->getId()]);
        }

        // Find User
        $user = $userRepository->findOneBy(['pseudo' => $identifier]);
        if (!$user) {
            $user = $userRepository->findOneBy(['email' => $identifier]);
        }

        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable avec ce pseudo ou email.');
            return $this->redirectToRoute('app_equipes_manage', ['id' => $equipe->getId()]);
        }

        // Check if user already has a team
        $existingMembership = $candidatureRepository->findOneBy(['user' => $user, 'statut' => 'Accepté']);
        if ($existingMembership) {
            $this->addFlash('error', 'Cet utilisateur est déjà membre de l\'équipe ' . $existingMembership->getEquipe()->getNomEquipe());
            return $this->redirectToRoute('app_equipes_manage', ['id' => $equipe->getId()]);
        }
        
        // Check/Update existing candidature or create new one
        $candidature = $candidatureRepository->findOneBy(['user' => $user, 'equipe' => $equipe]);
        if (!$candidature) {
            $candidature = new Candidature();
            $candidature->setUser($user);
            $candidature->setEquipe($equipe);
            $candidature->setMotivation('Ajouté par le manager');
            $candidature->setNiveau('Non spécifié'); // or default
            $candidature->setReason('Ajout manuel');
            $candidature->setPlayStyle('Non spécifié');
            $candidature->setRegion('Non spécifiée');
            $candidature->setDisponibilite('Non spécifiée');
        }
        
        $candidature->setStatut('Accepté');
        $candidature->setDateCandidature(new \DateTime());
        
        $entityManager->persist($candidature);
        $entityManager->flush();

        $this->addFlash('success', $user->getPseudo() . ' a été ajouté à l\'équipe avec succès.');
        return $this->redirectToRoute('app_equipes_manage', ['id' => $equipe->getId()]);
    }

    #[Route('/{id}/remove-member', name: 'app_equipes_remove_member', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeMember(Request $request, Equipe $equipe, EntityManagerInterface $entityManager, \App\Repository\CandidatureRepository $candidatureRepository, \App\Repository\UserRepository $userRepository): Response
    {
        // SECURITY CHECK
        $session = $request->getSession();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isManager = $this->isGranted('ROLE_MANAGER') && $session && $session->get('my_team_id') == $equipe->getId();

        if (!$isManager && !$isAdmin) {
            $this->addFlash('error', 'Accès refusé.');
            return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()]);
        }

        $userId = $request->request->get('user_id');
        $user = $userRepository->find($userId);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_equipes_manage', ['id' => $equipe->getId()]);
        }

        if ($user === $equipe->getManager()) {
            $this->addFlash('error', 'Vous ne pouvez pas retirer le manager de l\'équipe.');
            return $this->redirectToRoute('app_equipes_manage', ['id' => $equipe->getId()]);
        }

        $candidature = $candidatureRepository->findOneBy(['user' => $user, 'equipe' => $equipe, 'statut' => 'Accepté']);
        
        if ($candidature) {
            $entityManager->remove($candidature);
            $entityManager->flush();
            $this->addFlash('success', $user->getPseudo() . ' a été retiré de l\'équipe.');
        } else {
            $this->addFlash('warning', 'Cet utilisateur n\'est pas un membre actif de l\'équipe.');
        }

        return $this->redirectToRoute('app_equipes_manage', ['id' => $equipe->getId()]);
    }

    private function normalizeDiscordInvite(mixed $raw): ?string
    {
        $value = trim((string) ($raw ?? ''));
        if ($value == '') {
            return null;
        }

        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . $value;
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = (string) parse_url($value, PHP_URL_HOST);
        $path = (string) parse_url($value, PHP_URL_PATH);
        $isDiscordHost = str_contains($host, 'discord.gg')
            || str_contains($host, 'discord.com')
            || str_contains($host, 'discordapp.com');

        if (!$isDiscordHost) {
            return null;
        }

        if (str_contains($host, 'discord.com') && !str_starts_with($path, '/invite/')) {
            return null;
        }

        return $value;
    }
}

