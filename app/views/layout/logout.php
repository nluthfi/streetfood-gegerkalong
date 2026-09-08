<form action="<?= app_url('admin/auth/logout.php') ?>" method="POST" class="d-inline">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-link p-0 border-0 text-decoration-none">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </button>
</form>
