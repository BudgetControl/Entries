<?php

$app->get('/{wsis}', \Budgetcontrol\Entry\Controller\EntryController::class . ':get');
$app->get('/{wsis}/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':show');
$app->post('/{wsis}', \Budgetcontrol\Entry\Controller\EntryController::class . ':create');
$app->put('/{wsis}/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':update');
$app->delete('/{wsis}/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':delete');
