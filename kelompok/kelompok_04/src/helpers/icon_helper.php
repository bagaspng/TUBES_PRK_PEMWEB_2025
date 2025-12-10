<?php

function get_icon_map() {
    return [
        'home' => ['fa' => 'fa-solid fa-house', 'ion' => 'home-outline'],
        'document' => ['fa' => 'fa-solid fa-file-medical', 'ion' => 'document-text-outline'],
        'user-doctor' => ['fa' => 'fa-solid fa-user-doctor', 'ion' => 'medical'],
        'user' => ['fa' => 'fa-solid fa-user', 'ion' => 'person'],
        'users' => ['fa' => 'fa-solid fa-users', 'ion' => 'people'],
        'building' => ['fa' => 'fa-solid fa-building', 'ion' => 'business-outline'],
        'clipboard-check' => ['fa' => 'fa-solid fa-clipboard-check', 'ion' => 'clipboard-outline'],
        'megaphone' => ['fa' => 'fa-solid fa-bullhorn', 'ion' => 'megaphone-outline'],
        'calendar' => ['fa' => 'fa-solid fa-calendar-days', 'ion' => 'calendar-outline'],
        'plus' => ['fa' => 'fa-solid fa-plus', 'ion' => 'add-outline'],
    ];
}

function render_icon($name, $library = 'fa', $class = '', $ariaLabel = '') {
    $map = get_icon_map();
    $aria = !empty($ariaLabel) ? htmlspecialchars($ariaLabel) : ucfirst($name);
    
    if (isset($map[$name][$library])) {
        $iconClass = $map[$name][$library];
        
        if ($library === 'fa') {
            return sprintf(
                '<i class="%s %s" aria-hidden="true"></i><span class="sr-only">%s</span>',
                $iconClass,
                htmlspecialchars($class),
                $aria
            );
        } else {
            return sprintf(
                '<ion-icon name="%s" class="%s" aria-hidden="true"></ion-icon><span class="sr-only">%s</span>',
                $iconClass,
                htmlspecialchars($class),
                $aria
            );
        }
    }
    
    return sprintf(
        '<div class="icon-placeholder" data-icon="%s"><span class="sr-only">%s</span></div>',
        htmlspecialchars($name),
        $aria
    );
}