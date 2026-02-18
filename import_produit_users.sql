-- ============================================================
-- Import: produit + categorie + user data
-- Uses INSERT IGNORE to skip rows that already exist
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- --------------------------------------------------------
-- Catégories (needed for produit FK)
-- --------------------------------------------------------
INSERT IGNORE INTO `categorie` (`id`, `nom`) VALUES
(1, 'souris'),
(2, 'pc gamer'),
(3, 'clavier'),
(4, 'carte mre');

-- --------------------------------------------------------
-- Produits (with images)
-- --------------------------------------------------------
INSERT IGNORE INTO `produit` (`id`, `categorie_id`, `owner_user_id`, `owner_equipe_id`, `nom`, `prix`, `description`, `image`, `stock`, `statut`) VALUES
(1,  4, NULL, NULL, 'carte mere hytts',    100,  'La carte mère est le circuit imprimé principal d\'un ordinateur, agissant comme le système nerveux central qui interconnecte le processeur, la mémoire (RAM), le stockage et les périphériques. Elle gère l\'alimentation et la communication entre ces composants via des bus, tout en définissant les capacités d\'évolution de la machine.', 'uploads/images/Capture-d-ecran-2026-02-07-232806-6987c004318d1.png', 140, 'disponible'),
(3,  4, NULL, NULL, 'carte mere ttht7410', 140,  'La carte mère est le circuit imprimé principal d\'un ordinateur, agissant comme le système nerveux central qui interconnecte le processeur, la mémoire (RAM), le stockage et les périphériques. Elle gère l\'alimentation et la communication entre ces composants via des bus, tout en définissant les capacités d\'évolution de la machine.', 'uploads/images/Capture-d-ecran-2026-02-07-232800-6987bfea8b7f4.png', 21,  'disponible'),
(4,  2, NULL, NULL, 'pc gamer mpla',       140,  'Un ordinateur personnel (PC) est un appareil numérique polyvalent (travail, jeux, internet) composé d\'éléments matériels essentiels : processeur (cerveau), carte mère (connexion), mémoire RAM (temporaire), stockage SSD/HDD (permanent) et périphériques (écran, clavier, souris). Il fonctionne sous un système d\'exploitation qui gère le multitâche.', 'uploads/images/Capture-d-ecran-2026-02-07-232638-6987bfb876b63.png', 0,   'disponible'),
(5,  2, NULL, NULL, 'pc gamer',            1405, 'Un ordinateur personnel (PC) est un appareil numérique polyvalent (travail, jeux, internet) composé d\'éléments matériels essentiels : processeur (cerveau), carte mère (connexion), mémoire RAM (temporaire), stockage SSD/HDD (permanent) et périphériques (écran, clavier, souris). Il fonctionne sous un système d\'exploitation qui gère le multitâche.', 'uploads/images/Capture-d-ecran-2026-02-07-232644-6987bf9e010a5.png', 40,  'disponible'),
(6,  2, NULL, NULL, 'pc gamer',            1400, 'Un ordinateur personnel (PC) est un appareil numérique polyvalent (travail, jeux, internet) composé d\'éléments matériels essentiels : processeur (cerveau), carte mère (connexion), mémoire RAM (temporaire), stockage SSD/HDD (permanent) et périphériques (écran, clavier, souris). Il fonctionne sous un système d\'exploitation qui gère le multitâche.', 'uploads/images/Capture-d-ecran-2026-02-07-232649-6987bf647206e.png', 140, 'disponible'),
(10, 1, NULL, NULL, 'souris httys',        120,  'La souris est un petit mammifère rongeur (5-10 cm, 20-70 g) de la famille des muridés, caractérisé par un museau pointu, de grandes oreilles, une longue queue et un pelage souvent gris ou brun', 'uploads/images/Capture-d-ecran-2026-02-07-232715-6987bf8533615.png', 110, 'disponible'),
(11, 1, NULL, NULL, 'souris htx2012',      120,  'La souris est un petit mammifère rongeur (5-10 cm, 20-70 g) de la famille des muridés, caractérisé par un museau pointu, de grandes oreilles, une longue queue et un pelage souvent gris ou brun', 'uploads/images/Capture-d-ecran-2026-02-07-232705-6987bf38f2071.png', 140, 'disponible'),
(12, 3, NULL, NULL, 'clavier',             15,   'Un clavier d\'ordinateur est un périphérique d\'entrée essentiel, composé d\'environ 100 touches, permettant de saisir du texte, des chiffres et de commander un système informatique. Issu des machines à écrire, il se décline en versions filaires (USB) ou sans fil, avec des dispositions spécifiques comme l\'AZERTY (francophone) ou le QWERTY (anglophone), et inclut souvent un pavé numérique.', 'uploads/images/Capture-d-ecran-2026-02-07-232728-69885ea0f1647.png', 140, 'disponible'),
(13, 3, NULL, NULL, 'clavier tt47',        16,   'Un clavier d\'ordinateur est un périphérique d\'entrée essentiel, composé d\'environ 100 touches, permettant de saisir du texte, des chiffres et de commander un système informatique. Issu des machines à écrire, il se décline en versions filaires (USB) ou sans fil, avec des dispositions spécifiques comme l\'AZERTY (francophone) ou le QWERTY (anglophone), et inclut souvent un pavé numérique.', 'uploads/images/Capture-d-ecran-2026-02-07-232732-69885eba30249.png', 15,  'disponible');

-- --------------------------------------------------------
-- Users (anciens utilisateurs)
-- --------------------------------------------------------
INSERT IGNORE INTO `user` (`id`, `email`, `password`, `nom`, `role`, `pseudo`) VALUES
(1,  'admin@admin.com',              '$2y$13$o6YcNLpmzRJ.E5fiM4F8lOx.u/2wwF5QKTfOEhYIvR4KQcbZ.krSe', 'Admin',      'ROLE_ADMIN',    NULL),
(2,  'ghaiethbouamor23@gmail.com',   '$2y$13$/HD5uZXi9theKh3UhHsWy.7oO3w6G53Ep9puU0CCw0DhBKLd/n.I6', 'ali',        'ROLE_JOUEUR',   'gaaloul'),
(3,  'ghaiethbouamor013@gmail.com',  '$2y$13$2OjJLG7mu71vv2WQGqzLC.2N4mTCiPQEW2Jtpycgm8aA08wtxj8ZG', 'ghaieth',    'ROLE_JOUEUR',   'parker'),
(4,  'ghaiethbouamor773@gmail.com',  '$2y$13$jSxjw3XTopDlp8LVyeBhG.7275Zm1CS19OMkZBqLR3kybr/vl2XHO', 'ghaieth',    'ROLE_JOUEUR',   '7riga'),
(5,  'ilyeszid@esprit.tn',           '$2y$13$o6YcNLpmzRJ.E5fiM4F8lOx.u/2wwF5QKTfOEhYIvR4KQcbZ.krSe', '',           'ROLE_JOUEUR',   NULL),
(6,  'ilyes.zid@esprit.tn',          '$2y$13$lvBoJoMoPHDDyOJyjwfRu.H9J1lHxzBOENFKXgyCsyq6Yun2aaE6a', 'ilyes zid',  'ROLE_JOUEUR',   'ilyes'),
(8,  'ilyes14@gmail.com',            '$2y$13$4wFj53Knc/.qnqIwvY3IpeV0uxaOqvMep4X2n945MYXzdWC2kUz2W', 'ilyes',      'ROLE_JOUEUR',   'ilyes'),
(11, 'gmail@mohamed.nt',             '$2y$13$DRVaJyN3.1zoP3A7Xb0CyeQNK6rKCO/ZDLqrHbNr3yFtBWJxobPoW', 'mohamedaaa', 'ROLE_MANAGER',  'amnesia'),
(12, 'amen@amen.com',                '$2y$13$PoClSjS/niOHDOflV3zfwul5eiTSJR6q.8ThIzWO1Js48v9oyPfLe', 'amen',       'ROLE_MANAGER',  'amen123'),
(13, 'amenbensalah038@gmail.com',    '$2y$13$K9QaQXTVDGPqHF/L.y0JPubBEuEMPQA3RvssZdhig6RkqMn.mgIE2', 'amen',       'ROLE_JOUEUR',   'amen1'),
(14, 'mortadhaamaidi054@gmail.com',  '$2y$13$yrXuyYHM.Ds7gqFbEkbjieU0u3BGkA8IoRDbo0g6835GM7SrdUZVe', 'mortadha',   'ROLE_JOUEUR',   'aasbalyoussef');

SET foreign_key_checks = 1;
