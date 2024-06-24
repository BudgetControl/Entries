<?php

$app->get('/{wsis}', \Budgetcontrol\Entry\Controller\EntryController::class . ':get');

$app->get('/{wsis}/expense', \Budgetcontrol\Entry\Controller\ExpensesController::class . ':get');
$app->get('/{wsis}/expense/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':show');
$app->post('/{wsis}/expense', \Budgetcontrol\Entry\Controller\ExpensesController::class . ':create');
$app->put('/{wsis}/expense/{uuid}', \Budgetcontrol\Entry\Controller\ExpensesController::class . ':update');

$app->get('/{wsis}/income', \Budgetcontrol\Entry\Controller\IncomingController::class . ':get');
$app->get('/{wsis}/income/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':show');
$app->post('/{wsis}/income', \Budgetcontrol\Entry\Controller\IncomingController::class . ':create');
$app->put('/{wsis}/income/{uuid}', \Budgetcontrol\Entry\Controller\IncomingController::class . ':update');

$app->get('/{wsis}/transfer', \Budgetcontrol\Entry\Controller\TransferController::class . ':get');
$app->get('/{wsis}/transfer/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':show');
$app->post('/{wsis}/transfer', \Budgetcontrol\Entry\Controller\TransferController::class . ':create');
$app->put('/{wsis}/transfer/{uuid}', \Budgetcontrol\Entry\Controller\TransferController::class . ':update');

$app->get('/{wsis}/debit', \Budgetcontrol\Entry\Controller\DebitController::class . ':get');
$app->get('/{wsis}/debit/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':show');
$app->post('/{wsis}/debit', \Budgetcontrol\Entry\Controller\DebitController::class . ':create');
$app->put('/{wsis}/debit/{uuid}', \Budgetcontrol\Entry\Controller\DebitController::class . ':update');

$app->delete('/{wsis}/debit/{uuid}', \Budgetcontrol\Entry\Controller\DebitController::class . ':delete');
$app->delete('/{wsis}/income/{uuid}', \Budgetcontrol\Entry\Controller\IncomingController::class . ':delete');
$app->delete('/{wsis}/expense/{uuid}', \Budgetcontrol\Entry\Controller\ExpensesController::class . ':delete');
$app->delete('/{wsis}/transfer/{uuid}', \Budgetcontrol\Entry\Controller\TransferController::class . ':delete');
$app->delete('/{wsis}/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':delete');

$app->get('/{wsis}/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':show');
$app->put('/{wsis}/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':update');




