(function () {
    const root = document.querySelector('[data-user-performance-ai]');
    if (!root) {
        return;
    }

    const endpoint = root.getAttribute('data-endpoint');
    if (!endpoint) {
        root.innerHTML = '<div class="user-ai-empty">Endpoint IA introuvable.</div>';
        return;
    }

    const escapeHtml = function (value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    const formatTrend = function (trend) {
        if (trend === 'up') {
            return 'En progression';
        }
        if (trend === 'down') {
            return 'En baisse';
        }
        if (trend === 'stable') {
            return 'Stable';
        }
        return 'Donnees insuffisantes';
    };

    const formatConfidence = function (confidence) {
        if (confidence === 'high') {
            return 'Elevee';
        }
        if (confidence === 'medium') {
            return 'Moyenne';
        }
        return 'Faible';
    };

    const formatGameType = function (gameType) {
        if (gameType === 'fps') {
            return 'FPS';
        }
        if (gameType === 'sports') {
            return 'Sports';
        }
        if (gameType === 'battle_royale') {
            return 'Battle Royale';
        }
        if (gameType === 'mind') {
            return 'Mind';
        }
        return 'Other';
    };

    const resultClass = function (result) {
        if (result === 'W') {
            return 'win';
        }
        if (result === 'D') {
            return 'draw';
        }
        return 'loss';
    };

    const renderPrediction = function (prediction, modelInfo) {
        if (!prediction) {
            return '<div class="user-ai-chip muted">Prediction ML indisponible. Lance app:user-ai:train.</div>';
        }

        if (prediction.byGameType && typeof prediction.byGameType === 'object') {
            const status = modelInfo && modelInfo.status ? escapeHtml(modelInfo.status) : 'N/A';
            const gameTypeOrder = ['fps', 'sports', 'battle_royale', 'mind', 'other'];
            const allKeys = Object.keys(prediction.byGameType);
            const orderedKeys = gameTypeOrder.concat(allKeys.filter(function (key) {
                return gameTypeOrder.indexOf(key) === -1;
            }));

            const gameCards = orderedKeys
                .filter(function (key) {
                    return prediction.byGameType[key];
                })
                .map(function (key) {
                    const item = prediction.byGameType[key] || {};
                    const probability = Number(item.winProbability ?? 0);
                    const expected = escapeHtml(item.expectedResult ?? 'N/A');
                    const confidence = formatConfidence(item.confidence ?? 'low');
                    const samplesSeen = Number(item.samplesSeen ?? 0);
                    const modelName = escapeHtml(item.model ?? 'unknown');

                    return [
                        '<article class="user-ai-ml-game">',
                        '<div class="user-ai-ml-game-head">' + escapeHtml(formatGameType(key)) + '</div>',
                        '<div class="user-ai-ml-game-prob">' + probability + '%</div>',
                        '<div class="user-ai-ml-game-meta">Resultat: ' + expected + ' | Confiance: ' + confidence + '</div>',
                        '<div class="user-ai-ml-game-meta muted">Samples: ' + samplesSeen + ' | Model: ' + modelName + '</div>',
                        '</article>'
                    ].join('');
                }).join('');

            const bestType = prediction.bestGameType ? escapeHtml(formatGameType(prediction.bestGameType)) : null;
            const bestProbability = Number(prediction.bestWinProbability ?? 0);
            const bestChip = bestType
                ? '<span class="user-ai-chip">Meilleur type: ' + bestType + ' (' + bestProbability + '%)</span>'
                : '';

            return [
                '<div class="user-ai-ml">',
                '<div class="user-ai-ml-title"><i class="fas fa-robot"></i> Prediction ML (prochain match) par type</div>',
                '<div class="user-ai-ml-grid">',
                bestChip,
                '<span class="user-ai-chip muted">Status: ' + status + '</span>',
                '</div>',
                '<div class="user-ai-ml-types">' + gameCards + '</div>',
                '</div>'
            ].join('');
        }

        const probability = Number(prediction.winProbability ?? 0);
        const expected = escapeHtml(prediction.expectedResult ?? 'N/A');
        const confidence = formatConfidence(prediction.confidence ?? 'low');
        const samplesSeen = Number(prediction.samplesSeen ?? 0);
        const modelName = escapeHtml(prediction.model ?? 'unknown');
        const status = modelInfo && modelInfo.status ? escapeHtml(modelInfo.status) : 'N/A';

        return [
            '<div class="user-ai-ml">',
            '<div class="user-ai-ml-title"><i class="fas fa-robot"></i> Prediction ML (prochain match)</div>',
            '<div class="user-ai-ml-grid">',
            '<div class="user-ai-chip">Win probability: ' + probability + '%</div>',
            '<div class="user-ai-chip">Resultat attendu: ' + expected + '</div>',
            '<div class="user-ai-chip">Confiance: ' + confidence + '</div>',
            '<div class="user-ai-chip">Echantillons: ' + samplesSeen + '</div>',
            '<div class="user-ai-chip muted">Model: ' + modelName + '</div>',
            '<div class="user-ai-chip muted">Status: ' + status + '</div>',
            '</div>',
            '</div>'
        ].join('');
    };

    const render = function (data) {
        const summary = data.summary || {};
        const placements = data.placements || {};
        const perGame = data.perGame || {};
        const recentForm = Array.isArray(data.recentForm) ? data.recentForm : [];
        const recentMatches = Array.isArray(data.recentMatches) ? data.recentMatches : [];
        const quality = data.dataQuality || {};
        const mlPrediction = data.mlPrediction || null;
        const mlModelInfo = data.mlModelInfo || null;

        const cards = [
            { label: 'Tournois joues', value: summary.tournoisPlayed ?? 0 },
            { label: 'Matchs joues', value: summary.matchesPlayed ?? 0 },
            { label: 'Victoires', value: summary.wins ?? 0 },
            { label: 'Nuls', value: summary.draws ?? 0 },
            { label: 'Defaites', value: summary.losses ?? 0 },
            { label: 'Win rate', value: (summary.winRate ?? 0) + '%' },
            { label: 'Points totaux', value: summary.totalPoints ?? 0 },
            { label: 'Moyenne points/match', value: summary.averagePointsPerMatch ?? 0 }
        ];

        const bestGameType = summary.bestGameType
            ? '<span class="user-ai-chip">' + escapeHtml(summary.bestGameType) + ' (' + (summary.bestGameWinRate ?? 0) + '%)</span>'
            : '<span class="user-ai-chip muted">N/A</span>';

        const formHtml = recentForm.length > 0
            ? recentForm.map(function (result) {
                return '<span class="user-ai-form-item ' + resultClass(result) + '">' + escapeHtml(result) + '</span>';
            }).join('')
            : '<span class="user-ai-chip muted">Aucun resultat recent</span>';

        const perGameEntries = Object.keys(perGame).map(function (game) {
            const stats = perGame[game] || {};
            return {
                game: game,
                played: Number(stats.played ?? 0),
                winRate: Number(stats.winRate ?? 0),
                avgPoints: Number(stats.avgPoints ?? 0)
            };
        }).sort(function (a, b) {
            return b.played - a.played;
        });

        const barsHtml = perGameEntries.length > 0
            ? perGameEntries.map(function (entry) {
                const width = Math.max(0, Math.min(100, entry.winRate));
                return [
                    '<div class="user-ai-bar-row">',
                    '<div class="user-ai-bar-label">' + escapeHtml(entry.game) + '</div>',
                    '<div class="user-ai-bar-track"><span class="user-ai-bar-fill" style="width:' + width + '%"></span></div>',
                    '<div class="user-ai-bar-value">' + entry.winRate + '%</div>',
                    '</div>',
                    '<div class="user-ai-bar-meta">Played: ' + entry.played + ' | Avg points: ' + entry.avgPoints + '</div>'
                ].join('');
            }).join('')
            : '<div class="user-ai-empty">Aucune repartition par jeu disponible.</div>';

        const matchesHtml = recentMatches.length > 0
            ? [
                '<div class="user-ai-table-wrap">',
                '<table class="user-ai-table">',
                '<thead><tr><th>Date</th><th>Tournoi</th><th>Jeu</th><th>Res</th><th>Points</th><th>Source</th></tr></thead>',
                '<tbody>',
                recentMatches.map(function (match) {
                    const result = String(match.result ?? '-');
                    const resultCls = resultClass(result);
                    return [
                        '<tr>',
                        '<td>' + escapeHtml(match.date ?? '-') + '</td>',
                        '<td>' + escapeHtml(match.tournoi ?? '-') + '</td>',
                        '<td>' + escapeHtml(match.typeGame ?? '-') + '</td>',
                        '<td><span class="user-ai-form-item ' + resultCls + '">' + escapeHtml(result) + '</span></td>',
                        '<td>' + escapeHtml(match.points ?? 0) + '</td>',
                        '<td>' + escapeHtml(match.matchedBy ?? '-') + '</td>',
                        '</tr>'
                    ].join('');
                }).join(''),
                '</tbody>',
                '</table>',
                '</div>'
            ].join('')
            : '<div class="user-ai-empty">Aucun match recent a afficher.</div>';

        root.innerHTML = [
            '<div class="user-ai-header">',
            '<h3><i class="fas fa-brain"></i> Performance IA</h3>',
            '<span class="user-ai-chip">Profil Joueur</span>',
            '</div>',
            '<div class="user-ai-grid">',
            cards.map(function (card) {
                return [
                    '<article class="user-ai-stat">',
                    '<div class="user-ai-stat-label">' + escapeHtml(card.label) + '</div>',
                    '<div class="user-ai-stat-value">' + escapeHtml(card.value) + '</div>',
                    '</article>'
                ].join('');
            }).join(''),
            '</div>',
            '<div class="user-ai-row">',
            '<div><span class="user-ai-row-label">Meilleur type de jeu:</span> ' + bestGameType + '</div>',
            '<div><span class="user-ai-row-label">Tendance:</span> <span class="user-ai-chip">' + formatTrend(data.trend) + '</span></div>',
            '<div><span class="user-ai-row-label">Fiabilite:</span> <span class="user-ai-chip">' + formatConfidence(data.confidence) + '</span></div>',
            '</div>',
            '<div class="user-ai-row">',
            '<div><span class="user-ai-row-label">Podiums:</span> <span class="user-ai-chip">1st: ' + (placements.first ?? 0) + '</span> <span class="user-ai-chip">2nd: ' + (placements.second ?? 0) + '</span> <span class="user-ai-chip">3rd: ' + (placements.third ?? 0) + '</span></div>',
            '</div>',
            '<div class="user-ai-row">',
            '<div><span class="user-ai-row-label">Forme recente:</span> <span class="user-ai-form">' + formHtml + '</span></div>',
            '</div>',
            '<div class="user-ai-insight">',
            '<div class="user-ai-row-label">Analyse IA</div>',
            '<p>' + escapeHtml(data.aiInsight || 'Analyse indisponible.') + '</p>',
            '</div>',
            '<div class="user-ai-quality">',
            '<span class="user-ai-chip muted">Player links: ' + (quality.matchedByPlayerLink ?? 0) + '</span>',
            '<span class="user-ai-chip muted">Name alias: ' + (quality.matchedByNameAlias ?? 0) + '</span>',
            '<span class="user-ai-chip muted">Placements: ' + (quality.matchedByPlacement ?? 0) + '</span>',
            '<span class="user-ai-chip muted">Ambigus: ' + (quality.ambiguousSideMatches ?? 0) + '</span>',
            '</div>',
            renderPrediction(mlPrediction, mlModelInfo),
            '<details class="user-ai-details">',
            '<summary><i class="fas fa-chart-column"></i> Voir details et graphique</summary>',
            '<div class="user-ai-section-title">Win rate par type de jeu</div>',
            '<div class="user-ai-bars">' + barsHtml + '</div>',
            '<div class="user-ai-section-title">Historique recent</div>',
            matchesHtml,
            '</details>'
        ].join('');
    };

    root.innerHTML = '<div class="user-ai-loading"><i class="fas fa-spinner fa-spin"></i> Chargement de la performance IA...</div>';

    fetch(endpoint, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(function (data) {
            render(data);
        })
        .catch(function () {
            root.innerHTML = '<div class="user-ai-empty">Impossible de charger la performance IA pour le moment.</div>';
        });
})();
