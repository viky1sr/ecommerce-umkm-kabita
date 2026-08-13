<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\SuspendUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group User
 * @tag User - User management
 */
class UserController extends Controller
{
  /**
   * List all users with filters
   *
   * @authenticated
   * @query_param role string "Filter by role"
   * @query_param status string "Filter by status"
   * @query_param search string "Search by name or email"
   * @query_param sort string "Sort: newest|oldest" default=newest
   * @query_param per_page integer "Items per page" default=15
   * @response 200 body="{"success":true,"data":[{}],"meta":{"current_page":1,"per_page":15,"total":100,"last_page":7}}"
   */
  public function index(Request $request): JsonResponse
  {
    $query = User::query();

    // Filter by role
    if ($request->filled('role')) {
      $query->where('role', $request->role);
    }

    // Filter by status
    if ($request->filled('status')) {
      $query->where('status', $request->status);
    }

    // Search by name or email
    if ($request->filled('search')) {
      $query->where(function ($q) use ($request) {
        $q->where('name', 'like', '%' . $request->search . '%')
          ->orWhere('email', 'like', '%' . $request->search . '%');
      });
    }

    // Sorting
    match ($request->input('sort', 'newest')) {
      'oldest' => $query->orderBy('created_at', 'asc'),
      default => $query->orderBy('created_at', 'desc'),
    };

    $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
    $users = $query->paginate($perPage);

    return response()->json([
      'success' => true,
      'data' => UserResource::collection($users),
      'meta' => [
        'current_page' => $users->currentPage(),
        'per_page' => $users->perPage(),
        'total' => $users->total(),
        'last_page' => $users->lastPage(),
      ],
    ]);
  }

  /**
   * Suspend a user account (Admin)
   * @authenticated
   * @requestBody required
   * @bodyParam reason string required "Reason for suspension" example=Melanggar kebijakan platform
   * @response 200 body="{"success":true,"message":"Pengguna berhasil disuspend.","data":{}}"
   * @response 422 body="{"success":false,"message":"Pengguna sudah dalam status suspended."}"
   */
  public function suspend(User $user, SuspendUserRequest $request): JsonResponse
  {
    if ($user->status === UserStatus::SUSPENDED) {
      return response()->json([
        'success' => false,
        'message' => 'Pengguna sudah dalam status suspended.',
      ], 422);
    }

    $user->update([
      'status' => UserStatus::SUSPENDED,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Pengguna berhasil disuspend.',
      'data' => new UserResource($user),
    ]);
  }

  /**
   * Activate a suspended user account (Admin)
   * @authenticated
   * @response 200 body="{"success":true,"message":"Pengguna berhasil diaktifkan.","data":{}}"
   * @response 422 body="{"success":false,"message":"Pengguna tidak dalam status suspended."}"
   */
  public function activate(User $user): JsonResponse
  {
    if ($user->status !== UserStatus::SUSPENDED) {
      return response()->json([
        'success' => false,
        'message' => 'Pengguna tidak dalam status suspended.',
      ], 422);
    }

    $user->update([
      'status' => UserStatus::ACTIVE,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Pengguna berhasil diaktifkan.',
      'data' => new UserResource($user),
    ]);
  }
}
