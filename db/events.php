<?php

$handlers = array (
    'eval_complete' => array (
         'handlerfile'      => '/local/evaluations/lib.php',
         'handlerfunction'  => 'eval_complete_handler',
         'schedule'         => 'instant'
     ),
        'eval_created' => array (
         'handlerfile'      => '/local/evaluations/lib.php',
         'handlerfunction'  => 'eval_created_handler',
         'schedule'         => 'instant'
     )
);
?>
