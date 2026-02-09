{{-- Composant Dropdown Notifications pour la Navbar --}}
<div class="dropdown" id="notificationDropdown">
    <button class="btn btn-link nav-link position-relative p-2" type="button" 
            data-bs-toggle="dropdown" aria-expanded="false" id="notificationBtn">
        <i class="bi bi-bell fs-5"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" 
              id="notificationBadge">
            0
        </span>
    </button>
    
    <div class="dropdown-menu dropdown-menu-end shadow-lg" style="width: 380px; max-height: 500px;">
        {{-- En-tête --}}
        <div class="dropdown-header d-flex justify-content-between align-items-center py-2 px-3 bg-light">
            <span class="fw-bold">
                <i class="bi bi-bell me-1"></i> Notifications
            </span>
            <div>
                <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 me-2" 
                        id="markAllReadBtn" title="Tout marquer comme lu">
                    <i class="bi bi-check-all"></i>
                </button>
                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-link text-decoration-none p-0" 
                   title="Voir tout">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>
        </div>
        
        {{-- Liste des notifications --}}
        <div class="overflow-auto" style="max-height: 400px;" id="notificationList">
            <div class="text-center py-4 text-muted" id="notificationEmpty">
                <i class="bi bi-bell-slash fs-1"></i>
                <p class="mb-0 mt-2">Aucune notification</p>
            </div>
            {{-- Les notifications seront chargées ici via JavaScript --}}
        </div>
        
        {{-- Pied de page --}}
        <div class="dropdown-footer border-top py-2 px-3 text-center bg-light">
            <a href="{{ route('notifications.index') }}" class="text-decoration-none small">
                Voir toutes les notifications
            </a>
        </div>
    </div>
</div>

{{-- Son de notification --}}
<audio id="notificationSound" preload="auto">
    <source src="{{ asset('sounds/notification.mp3') }}" type="audio/mpeg">
    <source src="{{ asset('sounds/notification.ogg') }}" type="audio/ogg">
</audio>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationBadge = document.getElementById('notificationBadge');
    const notificationList = document.getElementById('notificationList');
    const notificationEmpty = document.getElementById('notificationEmpty');
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    const notificationSound = document.getElementById('notificationSound');
    
    let lastCheck = null;
    let previousUnreadCount = 0;

    // Charger les notifications
    function loadNotifications() {
        fetch('{{ route("notifications.latest") }}')
            .then(response => response.json())
            .then(data => {
                updateBadge(data.unread_count);
                renderNotifications(data.notifications);
                
                // Jouer le son si nouvelles notifications
                if (data.unread_count > previousUnreadCount) {
                    const hasSound = data.notifications.some(n => n.play_sound && !n.is_read);
                    if (hasSound) {
                        playSound();
                    }
                }
                previousUnreadCount = data.unread_count;
            })
            .catch(error => console.error('Erreur chargement notifications:', error));
    }

    // Mettre à jour le badge
    function updateBadge(count) {
        if (count > 0) {
            notificationBadge.textContent = count > 99 ? '99+' : count;
            notificationBadge.classList.remove('d-none');
        } else {
            notificationBadge.classList.add('d-none');
        }
    }

    // Afficher les notifications
    function renderNotifications(notifications) {
        if (notifications.length === 0) {
            notificationEmpty.classList.remove('d-none');
            return;
        }

        notificationEmpty.classList.add('d-none');
        
        let html = '';
        notifications.forEach(notification => {
            const unreadClass = notification.is_read ? '' : 'bg-light';
            const importantClass = notification.is_important ? 'border-start border-danger border-3' : '';
            
            html += `
                <div class="dropdown-item p-0 ${unreadClass} ${importantClass}" data-id="${notification.id}">
                    <a href="${notification.link || '#'}" class="d-flex align-items-start p-3 text-decoration-none text-dark notification-link"
                       data-id="${notification.id}">
                        <div class="flex-shrink-0 me-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-${notification.color} bg-opacity-10" 
                                  style="width: 40px; height: 40px;">
                                <i class="bi ${notification.icon} text-${notification.color}"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <strong class="mb-0 ${notification.is_read ? 'fw-normal' : ''}">${notification.title}</strong>
                                ${!notification.is_read ? '<span class="badge bg-primary rounded-pill" style="font-size: 0.6rem;">Nouveau</span>' : ''}
                            </div>
                            <p class="mb-1 small text-muted text-truncate" style="max-width: 250px;">${notification.message}</p>
                            <small class="text-muted"><i class="bi bi-clock me-1"></i>${notification.time_ago}</small>
                        </div>
                    </a>
                </div>
            `;
        });
        
        // Garder le message vide caché et ajouter les notifications
        notificationList.innerHTML = `
            <div class="text-center py-4 text-muted d-none" id="notificationEmpty">
                <i class="bi bi-bell-slash fs-1"></i>
                <p class="mb-0 mt-2">Aucune notification</p>
            </div>
            ${html}
        `;

        // Ajouter les événements de clic
        document.querySelectorAll('.notification-link').forEach(link => {
            link.addEventListener('click', function(e) {
                const id = this.dataset.id;
                markAsRead(id);
            });
        });
    }

    // Marquer comme lu
    function markAsRead(id) {
        fetch(`/notifications/${id}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            updateBadge(data.unread_count);
        });
    }

    // Marquer tout comme lu
    function markAllAsRead() {
        fetch('{{ route("notifications.mark-all-read") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            updateBadge(0);
            loadNotifications();
        });
    }

    // Jouer le son de notification
    function playSound() {
        if (notificationSound) {
            notificationSound.volume = 0.5;
            notificationSound.play().catch(e => {
                // Le navigateur peut bloquer l'autoplay
                console.log('Son de notification bloqué par le navigateur');
            });
        }
    }

    // Vérifier les nouvelles notifications (polling)
    function checkNewNotifications() {
        const url = lastCheck 
            ? `{{ route("notifications.check") }}?last_check=${encodeURIComponent(lastCheck)}`
            : '{{ route("notifications.check") }}';
            
        fetch(url)
            .then(response => response.json())
            .then(data => {
                lastCheck = data.timestamp;
                updateBadge(data.unread_count);
                
                // Si nouvelles notifications, recharger et alerter
                if (data.new_notifications.length > 0) {
                    loadNotifications();
                    
                    // Jouer le son si nécessaire
                    const hasSound = data.new_notifications.some(n => n.play_sound);
                    if (hasSound) {
                        playSound();
                    }

                    // Afficher notification browser si permission accordée
                    if (Notification.permission === 'granted') {
                        data.new_notifications.forEach(n => {
                            new Notification(n.title, {
                                body: n.message,
                                icon: '/favicon.ico'
                            });
                        });
                    }
                }
            })
            .catch(error => console.error('Erreur vérification notifications:', error));
    }

    // Demander permission pour les notifications browser
    function requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    // Événements
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            markAllAsRead();
        });
    }

    // Charger au clic sur le dropdown
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            loadNotifications();
        });
    }

    // Initialisation
    loadNotifications();
    requestNotificationPermission();

    // Polling toutes les 30 secondes
    setInterval(checkNewNotifications, 30000);
});
</script>
@endpush
