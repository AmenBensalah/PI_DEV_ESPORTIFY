# Team Rank IA (Manager Show)

Ce module calcule un rank IA de l'équipe à partir des statistiques:
- équilibre des rôles (`balanceScore`)
- niveau moyen des membres
- conversion candidatures récentes (acceptés / total)
- tendance des recrutements
- activité récente
- taille de roster

## Script ML
- Fichier: `ml/team_rank_predict.py`
- Entrée: JSON de stats équipe
- Sortie: JSON avec:
  - `rank_label`
  - `rank_score` (0..100)
  - `confidence` (0..99)
  - `reasons` (justifications)

## Intégration Symfony
- Service: `src/Service/TeamRankAiService.php`
- Contrôleur: `src/Controller/EquipesController.php` (`show`)
- Vue manager: `templates/equipes/show.html.twig`

Le service tente d'exécuter Python (`python`, `python3`, `py`).
Si Python est indisponible, fallback PHP automatique (même logique).

