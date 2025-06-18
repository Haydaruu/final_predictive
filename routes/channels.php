<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('order', function () {
    return auth()->check();
});

