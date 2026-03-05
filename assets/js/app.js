/**
 * Fuel Monitor Soyo - Main JavaScript
 */

const FuelMonitor = {
    baseUrl: '/',

    /**
     * Initialize application
     */
    init() {
        this.setupAjax();
        this.setupAutoRefresh();
    },

    /**
     * Setup default AJAX headers
     */
    setupAjax() {
        // Nothing special needed - we use fetch API
    },

    /**
     * Auto-refresh fuel availability every 30 seconds
     */
    setupAutoRefresh() {
        const fuelTable = document.getElementById('fuelAvailabilityTable');
        if (fuelTable) {
            setInterval(() => this.refreshFuelTable(), 30000);
        }
    },

    /**
     * Refresh fuel availability table via AJAX
     */
    async refreshFuelTable() {
        try {
            const response = await fetch(this.baseUrl + 'api/stations.php?action=availability');
            const data = await response.json();
            if (data.success) {
                this.updateFuelTable(data.stations);
            }
        } catch (error) {
            console.error('Error refreshing fuel table:', error);
        }
    },

    /**
     * Update fuel availability table DOM
     */
    updateFuelTable(stations) {
        const tbody = document.querySelector('#fuelAvailabilityTable tbody');
        if (!tbody) return;

        tbody.innerHTML = '';
        stations.forEach(station => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${this.escapeHtml(station.name)}</strong></td>
                <td>${this.escapeHtml(station.address)}</td>
                <td class="text-center">
                    <span class="fuel-status-indicator ${station.gasoline_available ? 'available' : 'unavailable'}"></span>
                    ${station.gasoline_available
                        ? '<span class="badge bg-success">Disponível</span>'
                        : '<span class="badge bg-danger">Indisponível</span>'}
                </td>
                <td class="text-center">
                    <span class="fuel-status-indicator ${station.diesel_available ? 'available' : 'unavailable'}"></span>
                    ${station.diesel_available
                        ? '<span class="badge bg-success">Disponível</span>'
                        : '<span class="badge bg-danger">Indisponível</span>'}
                </td>
                <td>${station.gasoline_price ? station.gasoline_price + ' Kz' : 'N/A'}</td>
                <td>${station.diesel_price ? station.diesel_price + ' Kz' : 'N/A'}</td>
                <td><small class="text-muted">${station.last_updated || 'N/A'}</small></td>
            `;
            tbody.appendChild(row);
        });
    },

    /**
     * Initialize Leaflet map
     */
    initMap(containerId, stations, center = [-6.1349, 12.3691], zoom = 13) {
        const map = L.map(containerId).setView(center, zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const gasIcon = L.divIcon({
            html: '<i class="bi bi-fuel-pump-fill text-primary" style="font-size:24px;"></i>',
            iconSize: [30, 30],
            className: 'map-icon'
        });

        stations.forEach(station => {
            const marker = L.marker([station.latitude, station.longitude], { icon: gasIcon }).addTo(map);

            const popupContent = `
                <div class="p-2">
                    <h6 class="mb-1">${this.escapeHtml(station.name)}</h6>
                    <p class="mb-1 small text-muted">${this.escapeHtml(station.address)}</p>
                    <div class="mb-1">
                        <strong>Gasolina:</strong>
                        ${station.gasoline_available
                            ? '<span class="text-success">Disponível</span>'
                            : '<span class="text-danger">Indisponível</span>'}
                    </div>
                    <div class="mb-1">
                        <strong>Gasóleo:</strong>
                        ${station.diesel_available
                            ? '<span class="text-success">Disponível</span>'
                            : '<span class="text-danger">Indisponível</span>'}
                    </div>
                    ${station.phone ? '<div class="small"><i class="bi bi-phone"></i> ' + this.escapeHtml(station.phone) + '</div>' : ''}
                </div>
            `;
            marker.bindPopup(popupContent);
        });

        return map;
    },

    /**
     * Search stations
     */
    async searchStations(query) {
        try {
            const response = await fetch(this.baseUrl + 'api/stations.php?action=search&q=' + encodeURIComponent(query));
            const data = await response.json();
            return data.success ? data.stations : [];
        } catch (error) {
            console.error('Error searching stations:', error);
            return [];
        }
    },

    /**
     * Update fuel availability (operator)
     */
    async updateFuel(stationId, fuelType, available, price, csrfToken) {
        try {
            const formData = new FormData();
            formData.append('station_id', stationId);
            formData.append('fuel_type', fuelType);
            formData.append('available', available ? 1 : 0);
            formData.append('price', price);
            formData.append('csrf_token', csrfToken);

            const response = await fetch(this.baseUrl + 'api/fuel.php', {
                method: 'POST',
                body: formData
            });
            return await response.json();
        } catch (error) {
            console.error('Error updating fuel:', error);
            return { success: false, message: 'Erro de conexão' };
        }
    },

    /**
     * Mark notification as read
     */
    async markNotificationRead(notificationId) {
        try {
            const response = await fetch(this.baseUrl + 'api/notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'mark_read', id: notificationId })
            });
            return await response.json();
        } catch (error) {
            console.error('Error:', error);
            return { success: false };
        }
    },

    /**
     * Mark all notifications as read
     */
    async markAllNotificationsRead() {
        try {
            const response = await fetch(this.baseUrl + 'api/notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'mark_all_read' })
            });
            return await response.json();
        } catch (error) {
            console.error('Error:', error);
            return { success: false };
        }
    },

    /**
     * Subscribe to station alerts
     */
    async subscribeAlerts(stationId, csrfToken) {
        try {
            const formData = new FormData();
            formData.append('action', 'subscribe');
            formData.append('station_id', stationId);
            formData.append('csrf_token', csrfToken);

            const response = await fetch(this.baseUrl + 'api/alerts.php', {
                method: 'POST',
                body: formData
            });
            return await response.json();
        } catch (error) {
            console.error('Error:', error);
            return { success: false, message: 'Erro de conexão' };
        }
    },

    /**
     * Unsubscribe from station alerts
     */
    async unsubscribeAlerts(stationId, csrfToken) {
        try {
            const formData = new FormData();
            formData.append('action', 'unsubscribe');
            formData.append('station_id', stationId);
            formData.append('csrf_token', csrfToken);

            const response = await fetch(this.baseUrl + 'api/alerts.php', {
                method: 'POST',
                body: formData
            });
            return await response.json();
        } catch (error) {
            console.error('Error:', error);
            return { success: false, message: 'Erro de conexão' };
        }
    },

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    },

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '11';
            document.body.appendChild(container);
        }

        const toastId = 'toast_' + Date.now();
        const bgClass = {
            'info': 'bg-primary',
            'success': 'bg-success',
            'warning': 'bg-warning',
            'danger': 'bg-danger'
        }[type] || 'bg-primary';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${this.escapeHtml(message)}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        document.getElementById('toastContainer').insertAdjacentHTML('beforeend', toastHtml);
        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => FuelMonitor.init());
