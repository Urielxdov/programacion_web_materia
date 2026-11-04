<?php
require __DIR__ . '/lib.php';
redirect(!usuario() ? 'login.php' : (usuario()['rol'] === 'admin' ? 'admin.php' : 'citas.php'));
