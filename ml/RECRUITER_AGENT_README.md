# Agent Recruteur Intelligent (gestion équipe)

Ce module IA est limité à la tâche recrutement/gestion d'équipe:
- comparer les profils candidats
- proposer automatiquement le Top 5
- filtrer par rank, région, disponibilité

## Fichiers
- `ml/recruitment_match_train.py` : entraîne le modèle (régression logistique simple).
- `ml/recruitment_training_dataset.csv` : dataset généré depuis les candidatures (créé pendant l'entraînement).
- `ml/recruitment_match_model.json` : modèle final chargé par Symfony pour scorer les candidats.

## Lancer l'entraînement
```bash
php bin/console app:recruitment-ai:train
```

La commande:
1. extrait les candidatures acceptées/refusées,
2. construit les features (`rank_gap`, `region_match`, `availability_score`, etc.),
3. entraîne le modèle Python,
4. génère `ml/recruitment_match_model.json`.

## Utilisation dans l'interface
- Page: `recrutements/manage`
- Bloc: "Top 5 joueurs compatibles"
- Filtres disponibles: `rank`, `region`, `availability`

