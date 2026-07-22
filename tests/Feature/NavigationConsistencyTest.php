<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Covers the navigation audit in SYSTEM_FLOW_REVIEW.md §8: the sidebar's
 * "Create Report" link was computed by role and then immediately hardcoded
 * to false, hiding it for every user; the "All Reports" link did not
 * highlight as active from its own detail page; and components.php carried
 * a dead, unreferenced duplicate sidebar/navbar definition pointing at a
 * nonexistent analytics.php route. These tests lock in the fixes so the
 * regressions cannot silently return.
 */
class NavigationConsistencyTest extends TestCase
{
    public function test_sidebar_does_not_hardcode_create_report_link_to_false(): void
    {
        $markup = file_get_contents(base_path('public/frontend/includes/sidebar.php'));
        $this->assertNotFalse($markup);

        $this->assertDoesNotMatchRegularExpression(
            '/\$showCreateReport\s*=\s*false\s*;/',
            $markup,
            'Create Report sidebar link must not be hardcoded to false.'
        );
    }

    public function test_sidebar_create_report_link_is_visible_to_every_role_that_can_submit_reports(): void
    {
        $markup = file_get_contents(base_path('public/frontend/includes/sidebar.php'));
        $this->assertNotFalse($markup);

        $this->assertMatchesRegularExpression(
            '/\$showCreateReport\s*=\s*in_array\(\s*\(\$user\[\'role\'\]\s*\?\?\s*\'\'\),\s*\[\'maintenance_staff\',\s*\'maintenance_admin\',\s*\'super_admin\'\],\s*true\s*\)\s*;/',
            $markup,
            'Create Report link must be shown to staff, maintenance_admin, and super_admin, matching reports.php\'s $canCreateReport gate and BUSINESS_RULES.md §1.'
        );
    }

    public function test_all_reports_link_highlights_active_from_its_own_detail_page(): void
    {
        $markup = file_get_contents(base_path('public/frontend/includes/sidebar.php'));
        $this->assertNotFalse($markup);

        $navStart = strpos($markup, 'data-page="reports"');
        $this->assertNotFalse($navStart, 'All Reports nav link not found.');

        $liStart = strrpos(substr($markup, 0, $navStart), '<li');
        $anchorMarkup = substr($markup, $liStart, $navStart - $liStart);

        $this->assertStringContainsString('maintenance-report-detail.php', $anchorMarkup, 'All Reports link must stay active when viewing a report detail page.');
    }

    public function test_components_file_no_longer_defines_dead_duplicate_sidebar(): void
    {
        $markup = file_get_contents(base_path('public/frontend/components/components.php'));
        $this->assertNotFalse($markup);

        $this->assertStringNotContainsString('function renderSidebar', $markup, 'Dead duplicate renderSidebar() (pointing at a nonexistent analytics.php route) must stay removed.');
        $this->assertStringNotContainsString('function renderNavbar', $markup, 'Dead duplicate renderNavbar() must stay removed.');
    }

    public function test_dashboard_no_buildings_notice_does_not_link_to_a_nonexistent_setup_page(): void
    {
        $markup = file_get_contents(base_path('public/frontend/pages/dashboard.php'));
        $this->assertNotFalse($markup);

        $this->assertStringNotContainsString('href="/School_Facility_Maintenance_System/backend/setup.html"', $markup, 'Dead link to nonexistent backend/setup.html must not be used as a navigation target.');
    }
}
