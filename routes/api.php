<?php

/*
 * Routes are not grouped so they can be searched easily.
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\FriendsController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post("/verify-email", [UserController::class, "verify_email"]);
Route::post("/reset-password", [UserController::class, "reset_password"]);
Route::post("/send-password-reset-link", [UserController::class, "send_password_reset_link"]);
Route::post("/login", [UserController::class, "login"]);
Route::post("/register", [UserController::class, "register"]);
Route::post("/admin/login", [AdminController::class, "login"]);

Route::post("/posts/fetch-shares", [PostsController::class, "fetch_shares"]);
Route::post("/posts/fetch-comments", [PostsController::class, "fetch_comments"]);
Route::post("/posts/fetch-likes", [PostsController::class, "fetch_likes"]);
Route::post("/posts/fetch", [PostsController::class, "fetch_single"]);
Route::post("/posts", [PostsController::class, "fetch"]);

Route::group([
    "middleware" => ["auth:sanctum"]
], function () {
    Route::post("/posts/comment", [PostsController::class, "comment"]);
    Route::post("/posts/toggle-like", [PostsController::class, "toggle_like"]);
    Route::post("/posts/delete", [PostsController::class, "destroy"]);
    Route::post("/posts/update", [PostsController::class, "update"]);
    Route::post("/posts/create", [PostsController::class, "create"]);

    Route::post("/friends/my", [FriendsController::class, "my"]);
    Route::post("/friends/remove-request", [FriendsController::class, "remove_request"]);
    Route::post("/friends/action-request", [FriendsController::class, "action_request"]);
    Route::post("/friends/send-request", [FriendsController::class, "send_request"]);

    Route::post("/messages/fetch", [MessagesController::class, "fetch"]);
    Route::post("/messages/send", [MessagesController::class, "send"]);

    Route::post("/change-password", [UserController::class, "change_password"]);
    Route::post("/save-profile", [UserController::class, "save_profile"]);
    Route::post("/logout", [UserController::class, "logout"]);
    Route::post("/me", [UserController::class, "me"]);

    Route::post("/admin/stats", [AdminController::class, "stats"]);
    Route::post("/admin/send-message", [AdminController::class, "send_message"]);
    Route::post("/admin/fetch-messages", [AdminController::class, "fetch_messages"]);
    Route::post("/admin/fetch-contacts", [AdminController::class, "fetch_contacts"]);
    Route::post("/admin/users/add", [AdminController::class, "add_user"]);
    Route::post("/admin/users/change-password", [AdminController::class, "change_user_password"]);
    Route::post("/admin/users/delete", [AdminController::class, "delete_user"]);
    Route::post("/admin/users/update", [AdminController::class, "update_user"]);
    Route::post("/admin/users/fetch/{id}", [AdminController::class, "fetch_single_user"]);
    Route::post("/admin/users/fetch", [AdminController::class, "fetch_users"]);
    Route::post("/admin/fetch-settings", [AdminController::class, "fetch_settings"]);
    Route::post("/admin/save-settings", [AdminController::class, "save_settings"]);
});
