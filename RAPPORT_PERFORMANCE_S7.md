# Rapport de Performance & Optimisation - Atelier Symfony 6.4

Date: 1 March 2026
Projet: E-Sportify

## 1) PHPStan

### Installation et configuration (atelier)
- Dependance presente: `phpstan/phpstan` (require-dev)
- Fichier: `phpstan.neon`
- Niveau configure: `level: 5` (conforme atelier, partie 3)
- Analyse ciblee demandee:
  - `vendor/bin/phpstan analyse src/Controller --level=5`
  - `vendor/bin/phpstan analyse src/Service --level=5`

### Avant optimisation (preuves)
- `src/Controller` (niveau 5): **77 erreurs**
  - preuve: `var/phpstan_controller_before.txt`
- `src/Service` (niveau 5): **45 erreurs**
  - preuve: `var/phpstan_service_before.txt`

### Corrections appliquees
1. `src/Service/GeminiService.php`
- simplification du controle d'erreur final (`$err`) pour supprimer les comparaisons nullables inutiles.

2. `src/Service/UserPerformanceAutoRetrainService.php`
- suppression d'un `is_string(PHP_BINARY)` inutile (constante deja string).

### Apres optimisation (preuves)
- `src/Controller` (niveau 5): **77 erreurs** (stable)
  - preuve: `var/phpstan_controller_after.txt`
- `src/Service` (niveau 5): **43 erreurs** (amelioration)
  - preuve: `var/phpstan_service_after.txt`

### Niveau 8 (partie 5 atelier)
- `src/Controller` (niveau 8): **189 erreurs**
  - preuve: `var/phpstan_controller_level8.txt`
- `src/Service` (niveau 8): **66 erreurs**
  - preuve: `var/phpstan_service_level8.txt`

### Ignore temporaire (partie 8 atelier)
- Config temporaire: `var/phpstan_ignore.neon`
- Regle: `#Call to an undefined method#`
- Resultat `src/Controller`: **58 erreurs** (contre 77 sans ignore)
  - preuve: `var/phpstan_controller_ignore.txt`

## 2) Tests unitaires (atelier "Chaque etudiant doit ...")

Entite choisie: `Produit`

Regles metier implementees:
1. Le nom du produit est obligatoire.
2. Le prix doit etre strictement superieur a zero.
3. Le stock ne peut pas etre negatif.

Service metier cree:
- `src/Service/ProduitManager.php`

Generation du test (make:test):
- commande executee: `php bin/console make:test TestCase ProduitManagerTest --no-interaction`

Test unitaire implemente:
- `tests/Service/ProduitManagerTest.php`
- cas couverts:
  - produit valide
  - produit sans nom
  - produit avec prix invalide
  - produit avec stock negatif

Execution:
- commande: `php bin/phpunit tests/Service/ProduitManagerTest.php --testdox`
- resultat: **OK (4 tests, 7 assertions)**

## 3) Doctrine Doctor

### Activation (atelier)
- bundle present: `config/bundles.php`
- activation dev: `config/packages/dev/doctrine_doctor.yaml` => `enabled: true`

### Mesure avant correction
- token profiler: `aaa1bd`
- nombre total de problemes: **70**
- repartition:
  - Integrity: 62
  - Database Config: 7
  - Security: 1
- preuve: `var/perf_before.txt`

### Probleme corrige
- Probleme detecte: `Timezone mismatch between MySQL and PHP`
- Correction appliquee:
  - `public/index.php` => `date_default_timezone_set('Europe/Paris');`
  - `bin/console` => `date_default_timezone_set('Europe/Paris');`

### Mesure apres correction
- token profiler: `aaa7db`
- nombre total de problemes: **69**
- repartition:
  - Integrity: 62
  - Database Config: 6
  - Security: 1
- preuve: `var/perf_after.txt`

## 4) Indicateurs de performance (table demandee)

Base de mesure:
- URL: `http://127.0.0.1:8000`
- 10 requetes par URL
- fonctionnalite principale choisie: `/login`

| Indicateur de performance | Avant optimisation (par defaut) | Apres optimisation | Preuves |
|---|---:|---:|---|
| Temps moyen de reponse page d'accueil (ms) | 1615.40 ms | 1602.11 ms | `var/perf_before.txt`, `var/perf_after.txt` |
| Temps d'execution fonctionnalite principale `/login` (ms) | 748.30 ms | 764.34 ms | `var/perf_before.txt`, `var/perf_after.txt` |
| Utilisation memoire (pic) | 48.00 MiB | 48.00 MiB | tokens `aaa1bd` / `aaa7db` |
| Nombre de problemes Doctrine Doctor | 70 | 69 | tokens `aaa1bd` / `aaa7db` |

## 5) Optimisations appliquees

1. `config/packages/web_profiler.yaml`
- `collect_serializer_data: true` -> `false`

2. `config/packages/doctrine.yaml`
- `profiling_collect_backtrace: '%kernel.debug%'` -> `false`

3. `config/packages/dev/doctrine_doctor.yaml`
- `enabled: false` -> `enabled: true`

4. `public/index.php` et `bin/console`
- alignement timezone PHP avec MySQL: `Europe/Paris`

5. Test unitaire atelier complet
- `src/Service/ProduitManager.php`
- `tests/Service/ProduitManagerTest.php`
