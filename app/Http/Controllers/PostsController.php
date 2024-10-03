<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Storage;
use Validator;

class PostsController extends Controller
{
    public function fetch_shares()
    {
        $validator = Validator::make(request()->all(), [
            "id" => "required"
        ]);

        if (!$validator->passes() && count($validator->errors()->all()) > 0)
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->all()[0]
            ]);
        }

        $time_zone = request()->time_zone ?? "";
        if (!empty($time_zone))
        {
            date_default_timezone_set($time_zone);
        }

        $posts_arr = [];
        $id = request()->id ?? 0;

        $post_shares = DB::table("post_shares")
            ->select("posts.*", "users.id AS user_id", "users.name AS user_name", "users.profile_image AS user_profile_image")
            ->join("posts", "posts.id", "=", "post_shares.shared_post_id")
            ->join("users", "users.id", "=", "post_shares.user_id")
            ->where("post_shares.post_id", "=", $id)
            ->orderBy("post_shares.id", "desc")
            ->get();

        foreach ($post_shares as $post)
        {
            $post_obj = [
                "id" => $post->id,
                "caption" => $post->caption ?? "",
                "user_id" => $post->user_id ?? "",
                "user_name" => $post->user_name ?? "",
                "user_profile_image" => null,
                "created_at" => date("Y-m-d h:i:s a", strtotime($post->created_at . " UTC"))
            ];

            if ($post->user_profile_image && Storage::exists("public/" . $post->user_profile_image))
            {
                $post_obj["user_profile_image"] = url("/storage/" . $post->user_profile_image);
            }

            array_push($posts_arr, $post_obj);
        }

        return response()->json([
            "status" => "success",
            "message" => "Data has been fetched.",
            "posts" => $posts_arr
        ]);
    }

    public function fetch_comments()
    {
        $validator = Validator::make(request()->all(), [
            "id" => "required"
        ]);

        if (!$validator->passes() && count($validator->errors()->all()) > 0)
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->all()[0]
            ]);
        }

        $time_zone = request()->time_zone ?? "";
        if (!empty($time_zone))
        {
            date_default_timezone_set($time_zone);
        }

        $post_comments_arr = [];
        $id = request()->id ?? 0;

        $post = DB::table("posts")
            ->where("id", "=", $id)
            ->first();

        if ($post == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "Post not found."
            ]);
        }

        $post_comments = DB::table("post_comments")
            ->select("post_comments.*", "users.name AS user_name", "users.profile_image AS user_profile_image")
            ->join("users", "users.id", "=", "post_comments.user_id")
            ->where("post_comments.post_id", "=", $id)
            ->orderBy("post_comments.id", "desc")
            ->paginate();

        foreach ($post_comments as $comment)
        {
            if (Storage::exists("public/" . $comment->user_profile_image))
            {
                $comment->user_profile_image = url("/storage/" . $comment->user_profile_image);
            }
            
            array_push($post_comments_arr, [
                "id" => $comment->id,
                "comment" => $comment->comment,
                "user_name" => $comment->user_name,
                "user_profile_image" => $comment->user_profile_image,
                "created_at" => date("d M Y, h:i:s a", strtotime($comment->created_at . " UTC"))
            ]);
        }

        return response()->json([
            "status" => "success",
            "message" => "Data has been fetched.",
            "comments" => $post_comments_arr
        ]);
    }

    public function comment()
    {
        $validator = Validator::make(request()->all(), [
            "id" => "required",
            "comment" => "required"
        ]);

        if (!$validator->passes() && count($validator->errors()->all()) > 0)
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->all()[0]
            ]);
        }

        $time_zone = request()->time_zone ?? "";
        if (!empty($time_zone))
        {
            date_default_timezone_set($time_zone);
        }

        $user = auth()->user();
        $id = request()->id ?? 0;
        $comment = request()->comment ?? "";

        $post = DB::table("posts")
            ->where("id", "=", $id)
            ->first();

        if ($post == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "Post not found."
            ]);
        }

        $comment_obj = [
            "user_id" => $user->id,
            "post_id" => $post->id,
            "comment" => $comment,
            "created_at" => now()->utc(),
            "updated_at" => now()->utc()
        ];

        $comment_obj["id"] = DB::table("post_comments")
            ->insertGetId($comment_obj);

        $comment_obj_arr = [
            "id" => $comment_obj["id"],
            "comment" => $comment_obj["comment"],
            "user_name" => $user->name,
            "user_profile_image" => "",
            "created_at" => date("d M Y, h:i:s a", strtotime($comment_obj["created_at"] . " UTC"))
        ];

        if ($user->profile_image && Storage::exists("public/" . $user->profile_image))
        {
            $comment_obj_arr["user_profile_image"] = url("storage/" . $user->profile_image);
        }

        return response()->json([
            "status" => "success",
            "message" => "Comment has been added.",
            "comment" => $comment_obj_arr
        ]);
    }

    public function fetch_likes()
    {
        $validator = Validator::make(request()->all(), [
            "id" => "required"
        ]);

        if (!$validator->passes() && count($validator->errors()->all()) > 0)
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->all()[0]
            ]);
        }

        $id = request()->id ?? 0;

        $post = DB::table("posts")
            ->where("id", "=", $id)
            ->first();

        if ($post == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "Post not found."
            ]);
        }

        $post_likers = DB::table("post_likers")
            ->select("users.id", "users.name", "users.profile_image")
            ->join("users", "users.id", "=", "post_likers.user_id")
            ->where("post_likers.post_id", "=", $post->id)
            ->orderBy("post_likers.id", "desc")
            ->paginate();

        $post_likers_arr = [];
        foreach ($post_likers as $post_liker)
        {
            $temp = [
                "id" => $post_liker->id,
                "name" => $post_liker->name,
                "profile_image" => ""
            ];

            if ($post_liker->profile_image && Storage::exists("public/" . $post_liker->profile_image))
            {
                $temp["profile_image"] = urL("/storage/" . $post_liker->profile_image);
            }
            else
            {
                $post_liker->profile_image = "";
            }

            array_push($post_likers_arr, $temp);
        }

        return response()->json([
            "status" => "success",
            "message" => "Data has been fetched.",
            "likers" => $post_likers_arr
        ]);
    }

    public function toggle_like()
    {
        $validator = Validator::make(request()->all(), [
            "id" => "required"
        ]);

        if (!$validator->passes() && count($validator->errors()->all()) > 0)
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->all()[0]
            ]);
        }

        $user = auth()->user();
        $id = request()->id ?? 0;
        $reaction = request()->reaction ?? "";

        if (!empty($reaction) && !in_array($reaction, ["like", "love", "angry", "sad", "laugh"]))
        {
            return response()->json([
                "status" => "error",
                "message" => "In-valid reaction."
            ]);
        }

        $post = DB::table("posts")
            ->where("id", "=", $id)
            ->first();

        if ($post == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "Post not found."
            ]);
        }

        $post_liker = DB::table("post_likers")
            ->where("post_id", "=", $post->id)
            ->where("user_id", "=", $user->id)
            ->first();

        $status = "";
        if ($post_liker == null)
        {
            DB::table("post_likers")
                ->insertGetId([
                "post_id" => $post->id,
                "user_id" => $user->id,
                "reaction" => $reaction,
                "created_at" => now()->utc(),
                "updated_at" => now()->utc()
            ]);

            DB::table("posts")
                ->where("id", "=", $post->id)
                ->increment("likes", 1);

            $status = "liked";
        }
        else
        {
            DB::table("post_likers")
                ->where("id", "=", $post_liker->id)
                ->delete();

            DB::table("posts")
                ->where("id", "=", $post->id)
                ->decrement("likes", 1);

            $status = "un_liked";
        }

        return response()->json([
            "status" => "success",
            "message" => "Post has been " . implode("-", explode("_", $status)) . "."
        ]);
    }

    public function destroy()
    {
        $validator = Validator::make(request()->all(), [
            "id" => "required"
        ]);

        if (!$validator->passes() && count($validator->errors()->all()) > 0)
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->all()[0]
            ]);
        }

        $user = auth()->user();
        $id = request()->id ?? 0;

        $post = DB::table("posts")
            ->where("id", "=", $id)
            ->where("user_id", "=", $user->id)
            ->first();

        if ($post == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "Post not found."
            ]);
        }

        $files = $post->files ?? "[]";
        $files = json_decode($files);

        for ($a = 0; $a < count($files); $a++)
        {
            if ($files[$a]->path && Storage::exists("public/" . $files[$a]->path))
            {
                Storage::delete("public/" . $files[$a]->path);
            }
        }

        DB::table("posts")
            ->where("id", "=", $post->id)
            ->delete();

        return response()->json([
            "status" => "success",
            "message" => "Post has been deleted."
        ]);
    }

    public function update()
    {
        $validator = Validator::make(request()->all(), [
            "id" => "required"
        ]);

        if (!$validator->passes() && count($validator->errors()->all()) > 0)
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->all()[0]
            ]);
        }

        $user = auth()->user();
        $id = request()->id ?? 0;
        $caption = request()->caption ?? "";
        $type = request()->type ?? "";
        $activity = request()->activity ?? "";
        $activity_value = request()->activity_value ?? "";
        $with_user = request()->with_user ?? 0; // this will be the ID of the user

        if (!empty($type) && !in_array($type, ["private", "public"]))
        {
            return response()->json([
                "status" => "error",
                "message" => "In-valid 'type' value."
            ]);
        }

        if (request()->file("files"))
        {
            foreach (request()->file("files") as $file)
            {
                $type = $file->getClientMimeType();
                if (!str_contains($type, "image") && !str_contains($type, "video"))
                {
                    return response()->json([
                        "status" => "error",
                        "message" => "Please select image or video files only."
                    ]);
                }
            }
        }

        $with_user_obj = null;
        if ($with_user > 0)
        {
            $with_user_obj = DB::table("users")
                ->where("id", "=", $with_user)
                ->first();

            if ($with_user_obj == null)
            {
                return response()->json([
                    "status" => "error",
                    "message" => "User not found."
                ]);
            }

            $is_friend = DB::table("friends")
                ->where(function ($query) use ($user, $with_user_obj) {
                    $query->where("user_1", "=", $user->id)
                        ->where("user_2", "=", $with_user_obj->id);
                })
                ->orWhere(function ($query) use ($user, $with_user_obj) {
                    $query->where("user_2", "=", $user->id)
                        ->where("user_1", "=", $with_user_obj->id);
                })
                ->where("status", "=", "accepted") // pending, rejected, accepted
                ->exists();

            if (!$is_friend)
            {
                return response()->json([
                    "status" => "error",
                    "message" => "User is not your friend."
                ]);
            }
        }

        $post = DB::table("posts")
            ->where("id", "=", $id)
            ->where("user_id", "=", $user->id)
            ->first();

        if ($post == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "Post not found."
            ]);
        }

        $post_obj = [
            "updated_at" => now()->utc()
        ];

        if (!empty($caption))
        {
            $post_obj["caption"] = $caption;
        }

        if (!empty($type))
        {
            $post_obj["type"] = $type;
        }

        if (!empty($activity))
        {
            $post_obj["activity"] = $activity;
        }

        $files = [];
        if (request()->file("files"))
        {
            $post_date = date("Y-m-d", strtotime($post->created_at));
            foreach (request()->file("files") as $file)
            {
                $file_path = "posts/" . $post_date . "/" . time() . "-" . $file->getClientOriginalName();
                $file->storeAs("public/", $file_path);

                array_push($files, [
                    "path" => $file_path,
                    "size" => $file->getSize(),
                    "type" => $file->getClientMimeType(),
                    "extension" => $file->getClientOriginalExtension()
                ]);
            }
        }

        if (count($files) > 0)
        {
            $post_obj["files"] = json_encode($files);
        }

        if ($with_user_obj != null)
        {
            $post_obj["with_user"] = $with_user_obj->id;
        }

        DB::table("posts")
            ->where("id", "=", $post->id)
            ->update($post_obj);

        return response()->json([
            "status" => "success",
            "message" => "Post has been updated."
        ]);
    }

    public function fetch_single()
    {
        $validator = Validator::make(request()->all(), [
            "id" => "required"
        ]);

        if (!$validator->passes() && count($validator->errors()->all()) > 0)
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->all()[0]
            ]);
        }

        $time_zone = request()->time_zone ?? "";
        if (!empty($time_zone))
        {
            date_default_timezone_set($time_zone);
        }

        $id = request()->id ?? 0;

        $post = DB::table("posts")
            ->select("posts.*", "users.name", "users.profile_image", "shared.caption AS shared_caption"
                , "shared.files AS shared_files", "shared.activity AS shared_activity")
            ->join("users", "users.id", "=", "posts.user_id")
            ->leftJoin("posts AS shared", "shared.id", "=", "posts.shared_post_id")
            ->where("posts.id", "=", $id)
            ->first();

        if ($post == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "Post not found."
            ]);
        }

        $post_arr = [];
        $files = $post->files ?? "[]";
        $files = json_decode($files);

        for ($a = 0; $a < count($files); $a++)
        {
            if ($files[$a]->path && Storage::exists("public/" . $files[$a]->path))
            {
                $files[$a]->path = url("/storage/" . $files[$a]->path);
            }
            else
            {
                $files[$a]->path = "";
            }
        }

        $shared_files = $post->shared_files ?? "[]";
        $shared_files = json_decode($shared_files);

        for ($a = 0; $a < count($shared_files); $a++)
        {
            if ($shared_files[$a]->path && Storage::exists("public/" . $shared_files[$a]->path))
            {
                $shared_files[$a]->path = url("/storage/" . $shared_files[$a]->path);
            }
            else
            {
                $shared_files[$a]->path = "";
            }
        }

        $post_arr = [
            "id" => $post->id,
            "caption" => $post->caption ?? "",
            "user_name" => $post->name ?? "",
            "profile_image" => $post->profile_image ?? "",
            "files" => $files,
            "type" => $post->type ?? "",
            "activity" => $post->activity ?? "",
            "with_user" => $post->with_user ?? null,
            "likes" => $post->likes ?? 0,
            "comments" => $post->comments ?? 0,
            "shares" => $post->shares ?? 0,
            "shared_post_id" => $post->shared_post_id ?? 0,
            "shared_caption" => $post->shared_caption ?? "",
            "shared_files" => $shared_files,
            "shared_activity" => $post->shared_activity ?? "",
            "created_at" => date("Y-m-d h:i:s a", strtotime($post->created_at . " UTC"))
        ];

        if (!is_null($post->with_user))
        {
            $post_arr["with_user"] = json_decode($post->with_user);
        }

        return response()->json([
            "status" => "success",
            "message" => "Data has been fetched.",
            "post" => $post_arr
        ]);
    }

    public function fetch()
    {
        $time_zone = request()->time_zone ?? "";
        if (!empty($time_zone))
        {
            date_default_timezone_set($time_zone);
        }
        
        $posts = DB::table("posts")
            ->select("posts.*", "users.name", "users.profile_image", "shared.caption AS shared_caption"
                , "shared.files AS shared_files", "shared.activity AS shared_activity")
            ->join("users", "users.id", "=", "posts.user_id")
            ->leftJoin("posts AS shared", "shared.id", "=", "posts.shared_post_id")
            ->inRandomOrder()
            ->paginate();

        $posts_arr = [];
        foreach ($posts as $post)
        {
            $files = $post->files ?? "[]";
            $files = json_decode($files);

            for ($a = 0; $a < count($files); $a++)
            {
                if ($files[$a]->path && Storage::exists("public/" . $files[$a]->path))
                {
                    $files[$a]->path = url("/storage/" . $files[$a]->path);
                }
                else
                {
                    $files[$a]->path = "";
                }
            }

            $shared_files = $post->shared_files ?? "[]";
            $shared_files = json_decode($shared_files);

            for ($a = 0; $a < count($shared_files); $a++)
            {
                if ($shared_files[$a]->path && Storage::exists("public/" . $shared_files[$a]->path))
                {
                    $shared_files[$a]->path = url("/storage/" . $shared_files[$a]->path);
                }
                else
                {
                    $shared_files[$a]->path = "";
                }
            }

            if (!is_null($post->with_user))
            {
                $post->with_user = json_decode($post->with_user);
            }

            array_push($posts_arr, [
                "id" => $post->id,
                "caption" => $post->caption ?? "",
                "user_name" => $post->name ?? "",
                "profile_image" => $post->profile_image ?? "",
                "files" => $files,
                "type" => $post->type ?? "",
                "activity" => $post->activity ?? "",
                "with_user" => $post->with_user ?? null,
                "likes" => $post->likes ?? 0,
                "comments" => $post->comments ?? 0,
                "shares" => $post->shares ?? 0,
                "shared_post_id" => $post->shared_post_id ?? 0,
                "shared_caption" => $post->shared_caption ?? "",
                "shared_files" => $shared_files,
                "shared_activity" => $post->shared_activity ?? "",
                "created_at" => $this->relative_time(time() - strtotime($post->created_at . " UTC"))
            ]);
        }

        return response()->json([
            "status" => "success",
            "message" => "Data has been fetched.",
            "posts" => $posts_arr
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $caption = request()->caption ?? "";
        $type = request()->type ?? "";
        $activity = request()->activity ?? "";
        $activity_value = request()->activity_value ?? "";
        $with_user = request()->with_user ?? 0; // this will be the ID of the user
        $shared_post_id = request()->shared_post_id ?? 0;

        $time_zone = request()->time_zone ?? "";
        if (!empty($time_zone))
        {
            $date_time_zone = new \DateTimeZone($time_zone);
        }

        if (!empty($type) && !in_array($type, ["private", "public"]))
        {
            return response()->json([
                "status" => "error",
                "message" => "In-valid 'type' value."
            ]);
        }

        if (!empty($activity) && !in_array($activity, ["Eating", "Reading", "Feeling", "Playing", "Doing", "Going"]))
        {
            return response()->json([
                "status" => "error",
                "message" => "In-valid 'activity' value."
            ]);
        }

        if (request()->file("files"))
        {
            foreach (request()->file("files") as $file)
            {
                $type = $file->getClientMimeType();
                if (!str_contains($type, "image") && !str_contains($type, "video"))
                {
                    return response()->json([
                        "status" => "error",
                        "message" => "Please select image or video files only."
                    ]);
                }
            }
        }

        $with_user_obj = null;
        if ($with_user > 0)
        {
            $with_user_obj = DB::table("users")
                ->where("id", "=", $with_user)
                ->first();

            if ($with_user_obj == null)
            {
                return response()->json([
                    "status" => "error",
                    "message" => "User not found."
                ]);
            }

            $is_friend = DB::table("friends")
                ->where(function ($query) use ($user, $with_user_obj) {
                    $query->where("user_1", "=", $user->id)
                        ->where("user_2", "=", $with_user_obj->id);
                })
                ->orWhere(function ($query) use ($user, $with_user_obj) {
                    $query->where("user_2", "=", $user->id)
                        ->where("user_1", "=", $with_user_obj->id);
                })
                ->where("status", "=", "accepted") // pending, rejected, accepted
                ->exists();

            if (!$is_friend)
            {
                return response()->json([
                    "status" => "error",
                    "message" => "User is not your friend."
                ]);
            }
        }

        $shared_post = null;

        if ($shared_post_id > 0)
        {
            $shared_post = DB::table("posts")
                ->where("id", "=", $shared_post_id)
                ->first();

            if ($shared_post == null)
            {
                return response()->json([
                    "status" => "error",
                    "message" => "Post not found."
                ]);
            }

            DB::table("posts")
                ->where("id", "=", $shared_post->id)
                ->increment("shares", 1);
        }

        $files = [];
        if (request()->file("files"))
        {
            $today = date("Y-m-d");
            foreach (request()->file("files") as $file)
            {
                $file_path = "posts/" . $today . "/" . time() . "-" . $file->getClientOriginalName();
                $file->storeAs("public", $file_path);

                // Get the full path to the folder
                $full_path = storage_path('app/public/posts');

                // Set permissions using PHP's chmod function
                chmod($full_path, 0775);

                // Get the full path to the folder
                $full_path = storage_path('app/public/posts/' . $today);

                // Set permissions using PHP's chmod function
                chmod($full_path, 0775);

                array_push($files, [
                    "path" => $file_path,
                    "size" => $file->getSize(),
                    "type" => $file->getClientMimeType(),
                    "extension" => $file->getClientOriginalExtension()
                ]);
            }
        }

        $post_obj = [
            "user_id" => $user->id,
            "caption" => $caption,
            "files" => json_encode($files),
            "type" => $type,
            "activity" => $activity,
            "activity_value" => $activity_value,
            "likes" => 0,
            "comments" => 0,
            "shares" => 0,
            "shared_post_id" => $shared_post_id,
            "created_at" => now()->utc(),
            "updated_at" => now()->utc()
        ];

        if ($with_user_obj != null)
        {
            $post_obj["with_user"] = $with_user_obj->id;
        }

        $post_obj["id"] = DB::table("posts")
            ->insertGetId($post_obj);

        if ($shared_post != null)
        {
            DB::table("post_shares")
                ->insertGetId([
                    "user_id" => $user->id,
                    "post_id" => $shared_post->id,
                    "shared_post_id" => $post_obj["id"],
                    "created_at" => now()->utc(),
                    "updated_at" => now()->utc()
                ]);
        }

        if (!empty($time_zone))
        {
            $date_time = new \DateTime($post_obj["created_at"]);
            $date_time->setTimezone($date_time_zone);
            $post_obj["created_at"] = $date_time->format("d M, Y h:i:s a");

            $date_time = new \DateTime($post_obj["updated_at"]);
            $date_time->setTimezone($date_time_zone);
            $post_obj["updated_at"] = $date_time->format("d M, Y h:i:s a");
        }

        foreach ($files as $key => $value)
        {
            if ($value["path"] && Storage::exists("public/" . $value["path"]))
            {
                $files[$key]["path"] = url("/storage/" . $value["path"]);
            }
        }

        $post_obj["files"] = $files;

        return response()->json([
            "status" => "success",
            "message" => "Post has been created.",
            "post" => $post_obj
        ]);
    }
}
