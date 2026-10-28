        </div>

        <footer class="app-footer">
            <span>&copy; <?= date('Y') ?> Stokara &middot; Sistem Inventaris Barang dan Transaksi Stok</span>
            <span class="footer-note">Dibuat untuk praktik pengembangan web</span>
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if (!empty($includePdf)): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<?php endif; ?>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<?php
old_input_clear();
form_errors_clear();
?>
</body>
</html>
