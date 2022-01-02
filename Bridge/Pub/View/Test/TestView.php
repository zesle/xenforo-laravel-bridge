<?php

namespace Laravel\Bridge\Pub\View\Test;

use XF\Mvc\View;

class TestView extends View
{
    public function renderJson()
    {
        return [
            'params' => $this->params['params'],
            'user_id' => $this->params['user_id'] ?? null,
            'session_id' => $this->params['session_id'] ?? null,
        ];
    }
}
