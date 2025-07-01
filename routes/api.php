<?php
$app->get('/monitor', \Budgetcontrol\Entry\Controller\Controller::class . ':monitor');

$app->get('/{wsid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':get');

$app->get('/{wsid}/expense', \Budgetcontrol\Entry\Controller\ExpensesController::class . ':get');
$app->get('/{wsid}/expense/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':show');
$app->post('/{wsid}/expense', \Budgetcontrol\Entry\Controller\ExpensesController::class . ':create');
$app->put('/{wsid}/expense/{uuid}', \Budgetcontrol\Entry\Controller\ExpensesController::class . ':update');

$app->get('/{wsid}/income', \Budgetcontrol\Entry\Controller\IncomingController::class . ':get');
$app->get('/{wsid}/income/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':show');
$app->post('/{wsid}/income', \Budgetcontrol\Entry\Controller\IncomingController::class . ':create');
$app->put('/{wsid}/income/{uuid}', \Budgetcontrol\Entry\Controller\IncomingController::class . ':update');

$app->get('/{wsid}/transfer', \Budgetcontrol\Entry\Controller\TransferController::class . ':get');
$app->get('/{wsid}/transfer/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':show');
$app->post('/{wsid}/transfer', \Budgetcontrol\Entry\Controller\TransferController::class . ':create');
$app->put('/{wsid}/transfer/{uuid}', \Budgetcontrol\Entry\Controller\TransferController::class . ':update');

$app->get('/{wsid}/debit', \Budgetcontrol\Entry\Controller\DebitController::class . ':get');
$app->get('/{wsid}/debit/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':show');
$app->post('/{wsid}/debit', \Budgetcontrol\Entry\Controller\DebitController::class . ':create');
$app->put('/{wsid}/debit/{uuid}', \Budgetcontrol\Entry\Controller\DebitController::class . ':update');

$app->get('/{wsid}/{goalUuid}/saving', \Budgetcontrol\Entry\Controller\SavingController::class . ':get');
$app->get('/{wsid}/saving/{uuid}', \Budgetcontrol\Entry\Controller\SavingController::class . ':show');
$app->post('/{wsid}/saving', \Budgetcontrol\Entry\Controller\SavingController::class . ':create');
$app->put('/{wsid}/saving/{uuid}', \Budgetcontrol\Entry\Controller\SavingController::class . ':update');

$app->delete('/{wsid}/debit/{uuid}', \Budgetcontrol\Entry\Controller\DebitController::class . ':delete');
$app->delete('/{wsid}/income/{uuid}', \Budgetcontrol\Entry\Controller\IncomingController::class . ':delete');
$app->delete('/{wsid}/expense/{uuid}', \Budgetcontrol\Entry\Controller\ExpensesController::class . ':delete');
$app->delete('/{wsid}/transfer/{uuid}', \Budgetcontrol\Entry\Controller\TransferController::class . ':delete');
$app->delete('/{wsid}/saving/{uuid}', \Budgetcontrol\Entry\Controller\SavingController::class . ':delete');
$app->delete('/{wsid}/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':delete');

$app->get('/{wsid}/model', \Budgetcontrol\Entry\Controller\ModelController::class . ':list');
$app->get('/{wsid}/model/{uuid}', \Budgetcontrol\Entry\Controller\ModelController::class . ':show');
$app->put('/{wsid}/model/{uuid}', \Budgetcontrol\Entry\Controller\ModelController::class . ':update');
$app->post('/{wsid}/model', \Budgetcontrol\Entry\Controller\ModelController::class . ':create');
$app->delete('/{wsid}/model/{uuid}', \Budgetcontrol\Entry\Controller\ModelController::class . ':delete');

$app->get('/{wsid}/planned-entry', \Budgetcontrol\Entry\Controller\PlannedEntryController::class . ':list');
$app->post('/{wsid}/planned-entry', \Budgetcontrol\Entry\Controller\PlannedEntryController::class . ':create');
$app->get('/{wsid}/planned-entry/{uuid}', \Budgetcontrol\Entry\Controller\PlannedEntryController::class . ':show');
$app->put('/{wsid}/planned-entry/{uuid}', \Budgetcontrol\Entry\Controller\PlannedEntryController::class . ':update');
$app->delete('/{wsid}/planned-entry/{uuid}', \Budgetcontrol\Entry\Controller\PlannedEntryController::class . ':delete');

$app->get('/{wsid}/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':show');
$app->put('/{wsid}/{uuid}', \Budgetcontrol\Entry\Controller\EntryController::class . ':update');




