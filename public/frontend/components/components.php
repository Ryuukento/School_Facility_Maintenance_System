<?php
/**
 * Navbar Component
 */
function renderNavbar($currentUser = null) {
    $userMenu = '';
    if ($currentUser) {
        $userMenu = <<<HTML
        <div class="navbar-user">
            <div class="user-avatar" data-user-avatar>
                {$currentUser['full_name'][0]}
            </div>
            <div class="dropdown">
                <button class="dropdown-toggle">{$currentUser['full_name']}</button>
                <ul class="dropdown-menu">
                    <li><a href="/School_Facility_Maintenance_System/frontend/pages/profile.php" class="dropdown-item">Profile</a></li>
                    <li><a href="/School_Facility_Maintenance_System/frontend/pages/account.php" class="dropdown-item">Account</a></li>
                    <li><hr style="margin: 0.5rem 0; border: none; border-top: 1px solid #e5e7eb;"></li>
                    <li><a href="#" data-logout class="dropdown-item">Logout</a></li>
                </ul>
            </div>
        </div>
        HTML;
    }
    
    $loginLink = '';
    if (!$currentUser) {
        $loginLink = '<a href="/School_Facility_Maintenance_System/frontend/pages/index.php" class="navbar-link">Login</a>';
    }
    
    return <<<HTML
    <nav class="navbar">
        <div class="navbar-container">
            <a href="/School_Facility_Maintenance_System/frontend/" class="navbar-brand">
                SFMS
            </a>
            <ul class="navbar-menu">
                <li><a href="/School_Facility_Maintenance_System/frontend/" class="navbar-link">Home</a></li>
                {$loginLink}
            </ul>
            {$userMenu}
        </div>
    </nav>
    HTML;
}

/**
 * Sidebar Component
 */
function renderSidebar($userRole = null) {
    $menuItems = [
        'dashboard' => [
            'label' => 'Dashboard',
            'url' => '/School_Facility_Maintenance_System/frontend/pages/dashboard.php',
            'roles' => ['super_admin', 'department_admin', 'user', 'maintenance_staff']
        ],
        'reports' => [
            'label' => 'Reports',
            'url' => '/School_Facility_Maintenance_System/frontend/pages/reports.php',
            'roles' => ['super_admin', 'department_admin', 'user', 'maintenance_staff']
        ],
        'users' => [
            'label' => 'Users',
            'url' => '/School_Facility_Maintenance_System/frontend/pages/users.php',
            'roles' => ['super_admin', 'department_admin']
        ],
        'analytics' => [
            'label' => 'Analytics',
            'url' => '/School_Facility_Maintenance_System/frontend/pages/analytics.php',
            'roles' => ['super_admin', 'department_admin']
        ]
    ];
    
    $menuHTML = '';
    foreach ($menuItems as $key => $item) {
        if (in_array($userRole, $item['roles'])) {
            $active = stripos($_SERVER['REQUEST_URI'], $item['url']) !== false ? 'active' : '';
            $menuHTML .= "<li class=\"sidebar-item\"><a href=\"{$item['url']}\" class=\"sidebar-link {$active}\">{$item['label']}</a></li>";
        }
    }
    
    return <<<HTML
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">SFMS</div>
        </div>
        <nav class="sidebar-nav">
            {$menuHTML}
        </nav>
    </aside>
    HTML;
}

/**
 * Alert Component
 */
function renderAlert($message, $type = 'info', $dismissible = true) {
    $closeBtn = $dismissible ? '<button class="close-btn" onclick="this.parentElement.remove()">×</button>' : '';
    
    return <<<HTML
    <div class="alert alert-{$type}">
        <div class="flex-between">
            <span>{$message}</span>
            {$closeBtn}
        </div>
    </div>
    HTML;
}

/**
 * Card Component
 */
function renderCard($title, $content, $footer = '') {
    $footerHTML = $footer ? "<div class=\"card-footer\">{$footer}</div>" : '';
    
    return <<<HTML
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">{$title}</h3>
        </div>
        <div class="card-body">
            {$content}
        </div>
        {$footerHTML}
    </div>
    HTML;
}

/**
 * Form Group Component
 */
function renderFormGroup($name, $label, $type = 'text', $value = '', $required = false, $options = []) {
    $requiredAttr = $required ? 'required' : '';
    $requiredLabel = $required ? '<span class="form-required">*</span>' : '';
    
    $fieldHTML = '';
    
    if ($type === 'select') {
        $optionsHTML = '';
        foreach ($options as $optVal => $optLabel) {
            $selected = $value === $optVal ? 'selected' : '';
            $optionsHTML .= "<option value=\"{$optVal}\" {$selected}>{$optLabel}</option>";
        }
        $fieldHTML = "<select name=\"{$name}\" {$requiredAttr}>{$optionsHTML}</select>";
    } elseif ($type === 'textarea') {
        $fieldHTML = "<textarea name=\"{$name}\" {$requiredAttr}>{$value}</textarea>";
    } else {
        $fieldHTML = "<input type=\"{$type}\" name=\"{$name}\" value=\"{$value}\" {$requiredAttr}>";
    }
    
    return <<<HTML
    <div class="form-group">
        <label for="{$name}">{$label} {$requiredLabel}</label>
        {$fieldHTML}
    </div>
    HTML;
}

/**
 * Table Component
 */
function renderTable($headers, $rows) {
    $headerHTML = '';
    foreach ($headers as $header) {
        $headerHTML .= "<th>{$header}</th>";
    }
    
    $rowsHTML = '';
    foreach ($rows as $row) {
        $rowsHTML .= "<tr>";
        foreach ($row as $cell) {
            $rowsHTML .= "<td>{$cell}</td>";
        }
        $rowsHTML .= "</tr>";
    }
    
    return <<<HTML
    <table class="table">
        <thead>
            <tr>{$headerHTML}</tr>
        </thead>
        <tbody>
            {$rowsHTML}
        </tbody>
    </table>
    HTML;
}

/**
 * Modal Component
 */
function renderModal($id, $title, $content, $footer = '') {
    $footerHTML = $footer ? "<div class=\"modal-footer\">{$footer}</div>" : '';
    
    return <<<HTML
    <div id="{$id}" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">{$title}</h2>
                <button class="modal-close">×</button>
            </div>
            <div class="modal-body">
                {$content}
            </div>
            {$footerHTML}
        </div>
    </div>
    HTML;
}

/**
 * Badge Component
 */
function renderBadge($text, $type = 'info') {
    return "<span class=\"badge badge-{$type}\">{$text}</span>";
}
