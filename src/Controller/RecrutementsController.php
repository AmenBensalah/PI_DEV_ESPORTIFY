<?php

namespace App\Controller;

use App\Entity\Recrutement;
use App\Form\RecrutementType;
use App\Repository\RecrutementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recrutements')]
final class RecrutementsController extends AbstractController
{
    #[Route(name: 'app_recrutements_index', methods: ['GET'])]
    public function index(RecrutementRepository $recrutementRepository): Response
    {
        return $this->render('recrutements/index.html.twig', [
            'recrutements' => $recrutementRepository->findAll(),
        ]);
    }

    #[Route('/manage/{id}', name: 'app_recrutements_manage', defaults: ['id' => null], methods: ['GET'])]
    public function manage(
        ?\App\Entity\Equipe $equipe,
        \App\Repository\CandidatureRepository $candidatureRepository,
        \App\Repository\EquipeRepository $equipeRepository,
        \App\Service\CandidatureScoreService $scoreService,
        \App\Service\CandidatureAiService $aiService,
        \App\Service\RecruiterAgentService $recruiterAgentService,
        \Doctrine\DBAL\Connection $connection,
        Request $request
    ): Response
    {
        // 1. Get the target team (either from URL {id} or session)
        if (!$equipe) {
            $session = $request->getSession();
            $teamId = $session->get('my_team_id');
            if ($teamId) {
                $equipe = $equipeRepository->find($teamId);
            }
        }

        if (!$equipe) {
             $this->addFlash('error', 'Aucune équipe sélectionnée ou trouvée.');
             return $this->redirectToRoute('app_equipes_index');
        }

        // 2. SECURITY CHECK: Only Manager or Admin
        $session = $request->getSession();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isManager = $this->isGranted('ROLE_MANAGER') && $session && $session->get('my_team_id') == $equipe->getId();

        if (!$isManager && !$isAdmin) {
             $this->addFlash('error', 'Accès refusé : Seuls les Managers peuvent gérer les recrutements.');
             return $this->redirectToRoute('app_equipes_show', ['id' => $equipe->getId()]);
        }

        // Fetch candidatures for this team
        $candidatures = $candidatureRepository->findBy(['equipe' => $equipe], ['dateCandidature' => 'DESC']);
        $aiEnabled = $aiService->isEnabled();
        $useAi = $request->query->get('ai') === '1' && $aiEnabled;
        $aiLimit = 8;
        $aiCount = 0;
        $aiInsights = [];
        $localScores = [];

        // Count stats
        $pendingCount = 0;
        $acceptedCount = 0;
        $refusedCount = 0;
        
        foreach ($candidatures as $candidature) {
            switch ($candidature->getStatut()) {
                case 'En attente':
                    $pendingCount++;
                    break;
                case 'Accepté':
                    $acceptedCount++;
                    break;
                case 'Refusé':
                    $refusedCount++;
                    break;
            }
        }

        foreach ($candidatures as $candidature) {
            $scoreData = $scoreService->score($candidature, $equipe);
            $localScores[$candidature->getId()] = $scoreData;
        }

        if ($useAi) {
            foreach ($candidatures as $candidature) {
                if ($candidature->getStatut() !== 'En attente') {
                    continue;
                }
                if ($aiCount >= $aiLimit) {
                    break;
                }
                $insight = $aiService->analyze($candidature, $equipe);
                if ($insight) {
                    $aiInsights[$candidature->getId()] = $insight;
                    $aiCount++;
                }
            }
        }

        usort($candidatures, function ($a, $b) use ($localScores) {
            $sa = $localScores[$a->getId()]['score'] ?? 0;
            $sb = $localScores[$b->getId()]['score'] ?? 0;
            return $sb <=> $sa;
        });

        $agentFilters = [
            'rank' => trim((string) $request->query->get('rank', '')),
            'region' => trim((string) $request->query->get('region', '')),
            'availability' => trim((string) $request->query->get('availability', '')),
        ];
        $topCandidates = $recruiterAgentService->topCandidates($equipe, $candidatures, $agentFilters, 5);

        $candidateUsers = [];
        if ($candidatures !== []) {
            $rows = $connection->fetchAllAssociative(
                'SELECT c.id AS candidature_id, u.id AS user_id, u.pseudo, u.nom, u.email
                 FROM candidature c
                 LEFT JOIN user u ON u.id = c.user_id
                 WHERE c.equipe_id = :teamId',
                ['teamId' => $equipe->getId()]
            );

            foreach ($rows as $row) {
                $cid = (int) ($row['candidature_id'] ?? 0);
                if ($cid <= 0) {
                    continue;
                }
                $candidateUsers[$cid] = [
                    'id' => isset($row['user_id']) ? (int) $row['user_id'] : null,
                    'pseudo' => isset($row['pseudo']) && $row['pseudo'] !== null && $row['pseudo'] !== '' ? (string) $row['pseudo'] : 'Utilisateur supprimé',
                    'nom' => isset($row['nom']) && $row['nom'] !== null && $row['nom'] !== '' ? (string) $row['nom'] : '-',
                    'email' => isset($row['email']) && $row['email'] !== null && $row['email'] !== '' ? (string) $row['email'] : '-',
                ];
            }
        }
        
        return $this->render('recrutements/manage.html.twig', [
            'candidatures' => $candidatures,
            'equipe' => $equipe,
            'pendingCount' => $pendingCount,
            'acceptedCount' => $acceptedCount,
            'refusedCount' => $refusedCount,
            'localScores' => $localScores,
            'aiInsights' => $aiInsights,
            'aiEnabled' => $aiEnabled,
            'useAi' => $useAi,
            'topCandidates' => $topCandidates,
            'agentFilters' => $agentFilters,
            'candidateUsers' => $candidateUsers,
        ]);
    }

    #[Route('/{id}/accept', name: 'app_recrutements_accept', methods: ['POST'])]
    public function accept(Request $request, \App\Entity\Candidature $candidature, EntityManagerInterface $entityManager): Response
    {
        // SECURITY CHECK: Only Manager of this team OR Admin
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $session = $request->getSession();
        $isManager = $this->isGranted('ROLE_MANAGER') && $session && $session->get('my_team_id') == $candidature->getEquipe()->getId();
        
        if (!$isAdmin && !$isManager) {
             $this->addFlash('error', 'Accès refusé.');
             return $this->redirectToRoute('fil_home');
        }

        $candidature->setStatut('Accepté');
        $entityManager->flush();
        
        $this->addFlash('success', 'Candidature acceptée avec succès !');
        return $this->redirectToRoute('app_recrutements_manage', ['id' => $candidature->getEquipe()->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/refuse', name: 'app_recrutements_refuse', methods: ['POST'])]
    public function refuse(Request $request, \App\Entity\Candidature $candidature, EntityManagerInterface $entityManager): Response
    {
        // SECURITY CHECK: Only Manager of this team OR Admin
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $session = $request->getSession();
        $isManager = $this->isGranted('ROLE_MANAGER') && $session && $session->get('my_team_id') == $candidature->getEquipe()->getId();
        
        if (!$isAdmin && !$isManager) {
             $this->addFlash('error', 'Accès refusé.');
             return $this->redirectToRoute('fil_home');
        }

        $candidature->setStatut('Refusé');
        $entityManager->flush();
        
        $this->addFlash('success', 'Candidature refusée.');
        return $this->redirectToRoute('app_recrutements_manage', ['id' => $candidature->getEquipe()->getId()], Response::HTTP_SEE_OTHER);
    }
}
