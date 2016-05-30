<?php

Route::group(['middleware' => ['web', 'auth', 'CanViewCRM']], function () {

    // Dashboard (overview) routes
    Route::get('/crm/dashboard', 'Rubenwouters\CrmLauncher\Controllers\DashboardController@index');
    Route::get('facebook', 'Rubenwouters\CrmLauncher\Controllers\DashboardController@askFbPermissions');
    Route::get('/callback', 'Rubenwouters\CrmLauncher\Controllers\DashboardController@fbCallback');

    // Publish routes
    Route::get('/crm/publisher', 'Rubenwouters\CrmLauncher\Controllers\PublishController@index');
    Route::get('/crm/publisher/{id}', 'Rubenwouters\CrmLauncher\Controllers\PublishController@detail');
    Route::post('/crm/publish', 'Rubenwouters\CrmLauncher\Controllers\PublishController@publish');
    Route::post('/crm/publisher/{id}/answer', 'Rubenwouters\CrmLauncher\Controllers\PublishController@replyTweet');
    Route::post('/crm/publisher/{id}/post', 'Rubenwouters\CrmLauncher\Controllers\PublishController@replyPost');

    // Cases routes (1/3) - general
    Route::get('/crm/cases', 'Rubenwouters\CrmLauncher\Controllers\CasesController@index'); // Dashboard overview
    Route::get('/crm/cases/filter', 'Rubenwouters\CrmLauncher\Controllers\CasesController@filter'); // Filter dashboard overview
    Route::get('/crm/case/{id}', 'Rubenwouters\CrmLauncher\Controllers\CasesController@detail'); // Detail page of a specific case
    Route::get('/crm/case/{caseId}/close', 'Rubenwouters\CrmLauncher\Controllers\CasesController@toggleCase'); // Close or re-open a case

    // Cases routes (2/3) - Facebook related
    Route::post('/crm/answer/{id}/post', 'Rubenwouters\CrmLauncher\Controllers\CasesController@replyPost'); // Reply to a Facebook post
    Route::post('/crm/answer/reply/{id}', 'Rubenwouters\CrmLauncher\Controllers\CasesController@replyPrivate'); // Reply to a Facebook post
    Route::get('/crm/case/{caseId}/post/{messageId}', 'Rubenwouters\CrmLauncher\Controllers\CasesController@deletePost'); // Delete Facebook post
    Route::get('/crm/case/{caseId}/inner/{messageId}', 'Rubenwouters\CrmLauncher\Controllers\CasesController@deleteInner'); // Delete inner Facebook post

    // Cases routes (3/3) - Twitter related
    Route::post('/crm/answer/{id}', 'Rubenwouters\CrmLauncher\Controllers\CasesController@replyTweet'); // Reply to a tweet
    Route::get('/crm/case/{caseId}/tweet/{messageId}', 'Rubenwouters\CrmLauncher\Controllers\CasesController@deleteTweet'); // Delete a tweet
    Route::get('/crm/case/{caseId}/follow', 'Rubenwouters\CrmLauncher\Controllers\CasesController@toggleFollowUser'); // Follow a user on Twitter


    // Case summary routes
    Route::post('/crm/case/{id}/summary/add', 'Rubenwouters\CrmLauncher\Controllers\SummaryController@addSummary'); // Add a case summary
    Route::get('/crm/case/{id}/summary/{summaryId}/delete', 'Rubenwouters\CrmLauncher\Controllers\SummaryController@deleteSummary'); // Delete a case summary

    // User management routes
    Route::get('/crm/users', 'Rubenwouters\CrmLauncher\Controllers\UsersController@index'); // Team overview
    Route::get('/crm/user/add', 'Rubenwouters\CrmLauncher\Controllers\UsersController@addUser'); // Add user
    Route::post('/crm/user/add', 'Rubenwouters\CrmLauncher\Controllers\UsersController@postUser'); // Team overview
    Route::post('/crm/user/{id}', 'Rubenwouters\CrmLauncher\Controllers\UsersController@toggleUser'); // Auth/de-auth user to see CRM
    Route::get('/crm/users/filter', 'Rubenwouters\CrmLauncher\Controllers\UsersController@searchUser'); // Search user by name/e-mail
});
