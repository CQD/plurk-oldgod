<?php

namespace Q\OldGod;

use Q\OldGod\OldGod;

class Home
{
    public function run()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $q = $input['q'];
            $chatlog = $input['chatlog'];
            $og = new OldGod();
            $result = ['ans' => $og->ask($q, $chatlog)];
            header('Cache-Control: no-store,max-age=0');
            header('Content-Type: application/json');
            echo json_encode($result);
            return;
        }
        header('Cache-Control: public,max-age=86400');
        require __DIR__ . '/../view/home.php';
    }
}
