<?php

namespace Q\OldGod;

use Q\OldGod\OldGod;

class Home
{
    public function run()
    {
        $q = $_GET['q'] ?? $_POST['q'] ?? null;
        if ($q) {
            $og = new OldGod();
            $result = ['ans' => $og->ask($q)];
            header('Cache-Control: no-store,max-age=0');
            header('Content-Type: application/json');
            echo json_encode($result);
            return;
        }
        header('Cache-Control: public,max-age=86400');
        require __DIR__ . '/../view/home.php';
    }
}
