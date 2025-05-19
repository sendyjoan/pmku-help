<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\User;

class UserAvatar extends Component
{
    public $user;
    public $class;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(User $user = null, $class = '')
    {
        $this->user = $user;
        $this->class = $class;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.user-avatar');
    }
}
