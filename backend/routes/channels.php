<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('branch.{branchId}.tables', function ($user, $branchId) {
    return (int) $user->branch_id === (int) $branchId;
});

Broadcast::channel('branch.{branchId}.kitchen', function ($user, $branchId) {
    return (int) $user->branch_id === (int) $branchId;
});

Broadcast::channel('branch.{branchId}.bar', function ($user, $branchId) {
    return (int) $user->branch_id === (int) $branchId;
});
