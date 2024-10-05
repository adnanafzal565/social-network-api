<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Storage;
use Validator;

class FriendsController extends Controller
{
    public function my()
    {
        $user = auth()->user();

        $time_zone = request()->time_zone ?? "";
        if (!empty($time_zone))
        {
            // $date_time_zone = new \DateTimeZone($time_zone);
            date_default_timezone_set($time_zone);
        }

        $friends = DB::table("friends")
            ->select("friends.*", "u1.name AS u1_name", "u2.name AS u2_name"
                , "u1.profile_image AS u1_profile_image", "u2.profile_image AS u2_profile_image")
            ->join("users AS u1", "u1.id", "=", "friends.user_1")
            ->join("users AS u2", "u2.id", "=", "friends.user_2")
            ->where("friends.user_1", "=", $user->id)
            ->orWhere("friends.user_2", "=", $user->id)
            ->orderBy("friends.id", "desc")
            ->paginate();

        $friends_arr = [];
        foreach ($friends as $friend)
        {
            $friend->u1_profile_image = $friend->u2_profile_image = "";
            if ($friend->u1_profile_image && Storage::exists("public/" . $friend->u1_profile_image))
            {
                $friend->u1_profile_image = url("/storage/" . $friend->u1_profile_image);
            }

            if ($friend->u2_profile_image && Storage::exists("public/" . $friend->u2_profile_image))
            {
                $friend->u2_profile_image = url("/storage/" . $friend->u2_profile_image);
            }

            // if (!empty($time_zone))
            {
                // $date_time = new \DateTime($friend->accepted_at);
                // $date_time->setTimezone($date_time_zone);
                // $friend->accepted_at = $date_time->format("d M, Y h:i:s a");
            }

            $friend->accepted_at = $this->relative_time(time() - strtotime($friend->accepted_at . " UTC"));

            array_push($friends_arr, [
                "id" => $friend->id,
                "u1_name" => $friend->u1_name,
                "u2_name" => $friend->u2_name,
                "u1_profile_image" => $friend->u1_profile_image,
                "u2_profile_image" => $friend->u2_profile_image,
                "status" => $friend->status,
                "accepted_at" => $friend->accepted_at
            ]);
        }

        return response()->json([
            "status" => "success",
            "message" => "Data has been fetched.",
            "friends" => $friends_arr
        ]);
    }

    public function remove_request()
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

        $friend = DB::table("friends")
            ->where("id", "=", $id)
            ->where("user_1", "=", $user->id)
            ->where("status", "=", "pending")
            ->first();

        if ($friend == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "Friend request not found."
            ]);
        }

        DB::table("friends")
            ->where("id", "=", $friend->id)
            ->delete();

        return response()->json([
            "status" => "success",
            "message" => "Friend request has been deleted."
        ]);
    }

    public function action_request()
    {
        $validator = Validator::make(request()->all(), [
            "id" => "required",
            "status" => "required"
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
        $status = request()->status ?? "";

        if (!in_array($status, ["rejected", "accepted"]))
        {
            return response()->json([
                "status" => "error",
                "message" => "In-valid 'status' value."
            ]);
        }

        $friend = DB::table("friends")
            ->where("id", "=", $id)
            ->where("user_2", "=", $user->id)
            ->where("status", "=", "pending")
            ->first();

        if ($friend == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "Friend request not found."
            ]);
        }

        $update_obj = [
            "status" => $status,
            "updated_at" => now()->utc()
        ];

        if ($status == "accepted")
        {
            $update_obj["accepted_at"] = now()->utc();
        }

        DB::table("friends")
            ->where("id", "=", $friend->id)
            ->update($update_obj);

        return response()->json([
            "status" => "success",
            "message" => "Friend request has been " . $status . "."
        ]);
    }

    public function send_request()
    {
        $validator = Validator::make(request()->all(), [
            "email" => "required"
        ]);

        if (!$validator->passes() && count($validator->errors()->all()) > 0)
        {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors()->all()[0]
            ]);
        }

        $user = auth()->user();
        $email = request()->email ?? "";

        $other_user = DB::table("users")
            ->where("email", "=", $email)
            ->first();

        if ($other_user == null)
        {
            return response()->json([
                "status" => "error",
                "message" => "User not found."
            ]);
        }

        $friend = DB::table("friends")
            ->where(function ($query) use ($user, $other_user) {
                $query->where("user_1", "=", $user->id)
                    ->where("user_2", "=", $other_user->id);
            })
            ->orWhere(function ($query) use ($user, $other_user) {
                $query->where("user_2", "=", $user->id)
                    ->where("user_1", "=", $other_user->id);
            })
            ->first();

        if ($friend != null)
        {
            if ($friend->status == "accepted")
            {
                return response()->json([
                    "status" => "error",
                    "message" => "Already friend."
                ]);
            }

            return response()->json([
                "status" => "error",
                "message" => "Friend request already sent."
            ]);
        }

        DB::table("friends")
            ->insertGetId([
                "user_1" => $user->id,
                "user_2" => $other_user->id,
                "status" => "pending",
                "created_at" => now()->utc(),
                "updated_at" => now()->utc()
            ]);

        return response()->json([
            "status" => "success",
            "message" => "Friend request has been sent."
        ]);
    }
}
