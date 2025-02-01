<?php

namespace App\View\Components;

use Illuminate\View\Component;

class NavLinkParent extends Component
{
    public function __construct(
        public bool $active = false,
        public string $href = ''
    ) {}

    public function render()
    {
        return view('livewire.layout.nav-link-parent');
    }
}