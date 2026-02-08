/**
 * E-Sportify - Main JavaScript File
 * Centralised JavaScript for all pages
 */

// ========================================
// THEME TOGGLE
// ========================================
document.addEventListener('DOMContentLoaded', function () {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);

            const icon = themeToggle.querySelector('i');
            const text = themeToggle.querySelector('span');
            if (newTheme === 'light') {
                icon.className = 'fas fa-sun';
                text.textContent = 'Mode Jour';
            } else {
                icon.className = 'fas fa-moon';
                text.textContent = 'Mode Nuit';
            }

            // Save preference
            localStorage.setItem('theme', newTheme);
        });

        // Load saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.body.setAttribute('data-theme', savedTheme);
            const icon = themeToggle.querySelector('i');
            const text = themeToggle.querySelector('span');
            if (savedTheme === 'light') {
                icon.className = 'fas fa-sun';
                text.textContent = 'Mode Jour';
            }
        }
    }
});

// ========================================
// MANAGER TEAM FUNCTIONS (index.html.twig)
// ========================================
window.myTeamId = null;

function confirmDeleteMyTeam() {
    if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer votre équipe ?\n\nCette action est irréversible et vous pourrez en créer une nouvelle après.')) {
        window.location.href = 'delete.html?id=' + window.myTeamId;
    }
}

// ========================================
// UI Helpers (global)
// - radio highlight
// - flash auto-hide
// - forward server flashes stored in hidden container
// ========================================
function initRadioHighlights() {
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const group = document.querySelectorAll(`input[name="${this.name}"]`);
            group.forEach(r => {
                if (r.parentElement) {
                    r.parentElement.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                    r.parentElement.style.background = 'transparent';
                }
            });
            if (this.parentElement) {
                this.parentElement.style.borderColor = 'var(--primary-blue)';
                this.parentElement.style.background = 'rgba(0, 217, 255, 0.1)';
            }
        });
    });
}

function initFlashAutoHide() {
    const flashes = document.querySelectorAll('.flash-success, .flash-error');
    if (!flashes.length) return;
    setTimeout(() => {
        flashes.forEach(el => {
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(() => el.remove(), 700);
        });
    }, 3000);
}

function initFlashForwarding() {
    try {
        const el = document.getElementById('flash-data');
        if (!el) return;
        const success = el.getAttribute('data-flashes-success');
        const error = el.getAttribute('data-flashes-error');
        const flashes = [];
        try { if (success) JSON.parse(success).forEach(m => flashes.push({ message: m })); } catch (e) { }
        try { if (error) JSON.parse(error).forEach(m => flashes.push({ message: m })); } catch (e) { }

        const forwarded = flashes.filter(f => {
            const s = (f.message || '').toLowerCase();
            return s.includes('cré') || s.includes('supprim') || s.includes('sélection');
        });

        if (forwarded.length > 0) {
            localStorage.setItem('flash_for_manage', JSON.stringify(forwarded));
        }
    } catch (e) {
        // noop
    }
}

// Initialize global UI helpers on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    initRadioHighlights();
    initFlashAutoHide();
    initFlashForwarding();
});

function loadManagerTeam() {
    // Simulation: Le manager n'a pas d'équipe
    const hasTeam = false;

    if (hasTeam) {
        const myTeam = {
            id: 4,
            name: "Phoenix Legends",
            rank: "Platine",
            description: "Notre équipe vise l'excellence dans tous les tournois !",
            date: "28/01/2025",
            recruits: 2,
            image: "images/phoenix.jpg"
        };
        displayManagerTeam(myTeam);
    } else {
        displayNoTeam();
    }
}

function displayManagerTeam(team) {
    window.myTeamId = team.id;

    const noTeamState = document.getElementById('no-team-state');
    const hasTeamState = document.getElementById('has-team-state');

    if (noTeamState) noTeamState.style.display = 'none';
    if (hasTeamState) hasTeamState.style.display = 'block';

    // Remplir les données
    const teamName = document.getElementById('team-name');
    const teamRank = document.getElementById('team-rank');
    const teamDescription = document.getElementById('team-description');
    const teamDate = document.getElementById('team-date');
    const teamRecruits = document.getElementById('team-recruits');
    const teamImage = document.getElementById('team-image');

    if (teamName) teamName.textContent = team.name;
    if (teamRank) teamRank.innerHTML = `<i class="fas fa-medal"></i> ${team.rank}`;
    if (teamDescription) teamDescription.textContent = team.description;
    if (teamDate) teamDate.textContent = team.date;
    if (teamRecruits) teamRecruits.textContent = team.recruits;
    if (teamImage) teamImage.src = team.image;
}

function displayNoTeam() {
    const noTeamState = document.getElementById('no-team-state');
    const hasTeamState = document.getElementById('has-team-state');

    if (noTeamState) noTeamState.style.display = 'block';
    if (hasTeamState) hasTeamState.style.display = 'none';
}

// ========================================

// Load manager team on index page
if (document.getElementById('my-team-container')) {
    loadManagerTeam();
}

// ========================================
// JOIN TEAM MODAL
// ========================================
function showJoinTeamModal() {
    const modal = document.getElementById('join-team-modal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeJoinTeamModal() {
    const modal = document.getElementById('join-team-modal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function searchTeams() {
    const searchInput = document.getElementById('team-search');
    if (!searchInput) return;

    const searchTerm = searchInput.value.toLowerCase().trim();
    const availableTeams = document.getElementById('available-teams');
    const noTeamsFound = document.getElementById('no-teams-found');

    if (!availableTeams) return;

    const teamCards = availableTeams.querySelectorAll('.card');
    let visibleCount = 0;

    teamCards.forEach(card => {
        const teamName = card.querySelector('h3')?.textContent.toLowerCase() || '';
        if (searchTerm === '' || teamName.includes(searchTerm)) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    if (visibleCount === 0) {
        availableTeams.style.display = 'none';
        if (noTeamsFound) noTeamsFound.style.display = 'block';
    } else {
        availableTeams.style.display = 'grid';
        if (noTeamsFound) noTeamsFound.style.display = 'none';
    }
}

function applyToTeam(teamId, teamName) {
    // Redirection directe vers le formulaire
    window.location.href = '/equipe/' + teamId + '/postuler';
}

// Close modal on ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeJoinTeamModal();
        closeEditModal();
        closeDeleteModal();
    }
});

// Search on Enter key
document.addEventListener('DOMContentLoaded', () => {
    const teamSearch = document.getElementById('team-search');
    if (teamSearch) {
        teamSearch.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchTeams();
            }
        });
    }
});

// ========================================
// TEAM CREATION FUNCTIONS (new.html.twig)
// ========================================
let teamData = null;

// Initialize page for team creation
document.addEventListener('DOMContentLoaded', function () {
    const teamDateInput = document.getElementById('teamDate');
    const teamIdInput = document.getElementById('teamId');

    if (teamDateInput) {
        const today = new Date().toISOString().split('T')[0];
        teamDateInput.value = today;
    }

    if (teamIdInput) {
        teamIdInput.value = 'EQ-' + Math.random().toString(36).substr(2, 8).toUpperCase();
    }

    // Check if team already exists in localStorage
    const savedTeam = localStorage.getItem('myTeam');
    if (savedTeam && document.getElementById('team-created-section')) {
        teamData = JSON.parse(savedTeam);
        showTeamCreatedSection();
        showTeamManagement();
    }

    // Add hover effect to logo upload
    const logoContainer = document.getElementById('logoPreviewContainer');
    const uploadOverlay = document.getElementById('uploadOverlay');
    if (logoContainer && uploadOverlay) {
        logoContainer.addEventListener('mouseenter', function () {
            uploadOverlay.style.opacity = '1';
        });
        logoContainer.addEventListener('mouseleave', function () {
            uploadOverlay.style.opacity = '0';
        });
    }
});

// Preview uploaded logo
function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const preview = document.getElementById('logoPreview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.border = '3px solid var(--primary-blue)';
            }
            window.teamLogoData = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Update visibility label
function updateVisibilityLabel() {
    const isPrivate = document.getElementById('teamPrivate')?.checked;
    const visibilityLabel = document.getElementById('visibilityLabel');
    const visibilityDescription = document.getElementById('visibilityDescription');

    if (visibilityLabel) {
        visibilityLabel.textContent = isPrivate ? 'Équipe Privée' : 'Équipe Publique';
    }
    if (visibilityDescription) {
        visibilityDescription.textContent = isPrivate
            ? 'Seuls les joueurs invités peuvent rejoindre votre équipe'
            : 'Tout le monde peut voir et demander à rejoindre votre équipe';
    }
}

function updateEditVisibilityLabel() {
    const isPrivate = document.getElementById('editTeamPrivate')?.checked;
    const editVisibilityLabel = document.getElementById('editVisibilityLabel');

    if (editVisibilityLabel) {
        editVisibilityLabel.textContent = isPrivate ? 'Équipe Privée' : 'Équipe Publique';
    }
}

// Create team
function createTeam(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    // Add logo data if present from some global variable (legacy support)
    if (window.teamLogoData && !formData.has('logo')) {
        formData.append('logo', window.teamLogoData);
    }

    const apiUrl = form.dataset.apiUrl || '/api/equipes/create';

    console.log('Tentative de création d\'équipe via:', apiUrl);

    fetch(apiUrl, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(result => {
            console.log('Réponse du serveur:', result);
            if (result.status === 200 || result.status === 201 || result.body.success) {
                const indexUrl = form.dataset.indexUrl || '/equipe/';
                window.location.href = indexUrl;
            } else {
                // AFFICHAGE DES ERREURS DU CONTROLLER
                if (result.body.errors && Array.isArray(result.body.errors)) {
                    // On affiche chaque erreur individuellement
                    result.body.errors.forEach(msg => {
                        showNotification(msg, 'error');
                    });
                } else {
                    // Message de secours si le format est différent
                    showNotification(result.body.message || "Une erreur est survenue lors de la création.", 'error');
                }
            }
        })
        .catch(error => {
            console.error('Erreur réseau:', error);
            showNotification('Erreur réseau ou serveur: ' + (error.message || 'Inconnue'), 'error');
        });

    return false;
}

function showTeamCreatedSection() {
    const createSection = document.getElementById('create-team-section');
    const createdSection = document.getElementById('team-created-section');

    if (createSection) createSection.style.display = 'none';
    if (createdSection) createdSection.style.display = 'block';

    if (teamData) {
        updateDisplayedTeamInfo();
    }
}

function showTeamManagement() {
    const management = document.getElementById('team-management');
    const successCard = document.querySelector('.success-card');

    if (management) management.style.display = 'block';
    if (successCard) successCard.style.display = 'none';

    updateDisplayedTeamInfo();
}

function updateDisplayedTeamInfo() {
    if (!teamData) return;

    const displayTeamName = document.getElementById('displayTeamName');
    const displayId = document.getElementById('displayId');
    const displayTag = document.getElementById('displayTag');
    const displayDate = document.getElementById('displayDate');
    const displayMaxMembers = document.getElementById('displayMaxMembers');
    const displayRegion = document.getElementById('displayRegion');
    const displayVisibility = document.getElementById('displayVisibility');
    const displayDescription = document.getElementById('displayDescription');
    const maxMemberDisplay = document.getElementById('maxMemberDisplay');

    if (displayTeamName) displayTeamName.textContent = '[' + teamData.tag + '] ' + teamData.name;
    if (displayId) displayId.textContent = teamData.id;
    if (displayTag) displayTag.textContent = '[' + teamData.tag + ']';
    if (displayDate) displayDate.textContent = formatDate(teamData.date);
    if (displayMaxMembers) displayMaxMembers.textContent = teamData.maxMembers + ' Membres';
    if (displayRegion) displayRegion.textContent = teamData.region;
    if (displayVisibility) displayVisibility.textContent = teamData.isPrivate ? '🔒 Privée' : '🌐 Publique';
    if (displayDescription) displayDescription.textContent = teamData.description;
    if (maxMemberDisplay) maxMemberDisplay.textContent = teamData.maxMembers;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

// ========================================
// EDIT/DELETE MODALS
// ========================================
function openEditModal() {
    const modal = document.getElementById('editModal');
    if (!modal || !teamData) return;

    modal.style.display = 'block';

    const editTeamName = document.getElementById('editTeamName');
    const editMaxMembers = document.getElementById('editMaxMembers');
    const editRegion = document.getElementById('editRegion');
    const editTeamPrivate = document.getElementById('editTeamPrivate');
    const editDescription = document.getElementById('editDescription');

    if (editTeamName) editTeamName.value = teamData.name;
    if (editMaxMembers) editMaxMembers.value = teamData.maxMembers;
    if (editRegion) editRegion.value = teamData.region;
    if (editTeamPrivate) editTeamPrivate.checked = teamData.isPrivate;
    if (editDescription) editDescription.value = teamData.description;

    updateEditVisibilityLabel();
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function updateTeam(event) {
    event.preventDefault();

    if (!teamData) return false;

    teamData.name = document.getElementById('editTeamName')?.value || teamData.name;
    teamData.maxMembers = document.getElementById('editMaxMembers')?.value || teamData.maxMembers;
    teamData.region = document.getElementById('editRegion')?.value || teamData.region;
    teamData.isPrivate = document.getElementById('editTeamPrivate')?.checked || false;
    teamData.description = document.getElementById('editDescription')?.value || teamData.description;

    localStorage.setItem('myTeam', JSON.stringify(teamData));
    updateDisplayedTeamInfo();
    closeEditModal();

    showNotification('Équipe mise à jour avec succès !', 'success');

    return false;
}

function openDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function deleteTeam() {
    localStorage.removeItem('myTeam');
    teamData = null;
    closeDeleteModal();

    // Redirect to index page
    const indexUrl = document.body.dataset.indexUrl || '/equipe';
    window.location.href = indexUrl;
}

// Close modals on outside click
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');

    if (editModal) {
        editModal.addEventListener('click', function (e) {
            if (e.target === this) closeEditModal();
        });
    }

    if (deleteModal) {
        deleteModal.addEventListener('click', function (e) {
            if (e.target === this) closeDeleteModal();
        });
    }
});

// ========================================
// NOTIFICATIONS
// ========================================
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = 'notification notification-' + type;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 20px 30px;
        background: ${type === 'success' ? 'var(--success-color)' : 'var(--primary-pink)'};
        color: white;
        border-radius: 12px;
        font-weight: 600;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    `;
    notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ========================================
// RECRUITMENT FILTER FUNCTIONS
// ========================================
function filterRequests(status) {
    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
        // Simple check based on text content or data attribute if simpler
        // Here we rely on the text content mapping from the original code
        const tabText = tab.textContent.toLowerCase();
        if ((status === 'all' && tabText.includes('toutes')) ||
            (status === 'pending' && tabText.includes('en attente')) ||
            (status === 'accepted' && tabText.includes('acceptées')) ||
            (status === 'refused' && tabText.includes('refusées'))) {
            tab.classList.add('active');
        }
    });

    // Filter cards
    document.querySelectorAll('.recruitment-card').forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
            card.classList.add('animate__fadeIn');
        } else {
            card.style.display = 'none';
            card.classList.remove('animate__fadeIn');
        }
    });
}
