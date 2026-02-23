# Feed AI Auto-Retrain

## 1) Installer les dependances ML

```powershell
python -m pip install -r ml/requirements.txt
```

Paquets clés utilisés:

- `pandas`
- `scikit-learn`
- `joblib`
- `datasets` (ingestion de datasets publics web)

## 2) Entrainement manuel

```powershell
php bin/console app:feed-ai:train
```

Entrainement enrichi web (recommande):

```powershell
php bin/console app:feed-ai:train --with-web-data=1 --web-max-rows=45000 --web-languages=fr,en,ar
```

## 3) Auto-retrain avec seuils qualite

Commande:

```powershell
php bin/console app:feed-ai:auto-retrain --allow-fallback
```

Options utiles:

- `--cooldown-minutes=180`: evite relancement trop frequent
- `--min-rows=30`: minimum de lignes dataset
- `--min-category-score=0.70`: seuil qualite classification categorie (mode sklearn)
- `--min-action-score=0.70`: seuil qualite classification moderation (mode sklearn)
- `--max-spam-mae=12`: seuil erreur spam (mode sklearn)
- `--allow-fallback`: autorise activation modele fallback si sklearn indisponible
- `--with-web-data=1`: active l ingestion de datasets internet publics
- `--web-max-rows=45000`: limite les lignes importees depuis le web
- `--web-languages=fr,en,ar`: langues cibles pour les echantillons web/synthetiques
- `--force`: ignore cooldown

Etat auto-retrain:

- `var/feed_ai/auto_retrain_state.json`

Modeles actifs:

- `var/feed_ai/models/*`

## 4) Planification periodique (cron)

Exemple toutes les 3 heures:

```cron
0 */3 * * * cd /path/to/PI_DEV_ESPORTIFY && php bin/console app:feed-ai:auto-retrain --allow-fallback --no-interaction >> var/log/feed_ai_retrain.log 2>&1
```

## 5) Planification Windows (Task Scheduler)

Action programme:

- Programme/script: `php`
- Arguments:
  - `bin/console app:feed-ai:auto-retrain --allow-fallback --no-interaction`
- Demarrer dans:
  - `C:\Users\bouzi\Desktop\PI_DEV_ESPORTIFY`

Frequence recommandee:

- toutes les 3h (ou 1 fois/nuit pour debuter)
