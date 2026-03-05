<!-- Footer -->
<footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3">
                <h5><i class="bi bi-fuel-pump-fill"></i> Fuel Monitor Soyo</h5>
                <p class="text-muted small">
                    Sistema de monitoramento de disponibilidade de combustíveis
                    na cidade do Soyo, Angola.
                </p>
            </div>
            <div class="col-md-4 mb-3">
                <h6>Links Rápidos</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= BASE_URL ?>index.php" class="text-muted text-decoration-none">Início</a></li>
                    <li><a href="<?= BASE_URL ?>stations.php" class="text-muted text-decoration-none">Postos</a></li>
                    <li><a href="<?= BASE_URL ?>register.php" class="text-muted text-decoration-none">Cadastrar</a></li>
                    <li><a href="<?= BASE_URL ?>request-station.php" class="text-muted text-decoration-none">Solicitar Posto</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-3">
                <h6>Contacto</h6>
                <ul class="list-unstyled small text-muted">
                    <li><i class="bi bi-geo-alt"></i> Soyo, Zaire, Angola</li>
                    <li><i class="bi bi-envelope"></i> info@fuelsoyo.com</li>
                    <li><i class="bi bi-phone"></i> +244 923 000 000</li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="text-center text-muted small">
            &copy; <?= date('Y') ?> Fuel Monitor Soyo. Todos os direitos reservados.
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Custom JS -->
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
