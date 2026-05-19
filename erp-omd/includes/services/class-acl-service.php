<?php

class ERP_OMD_Acl_Service
{
    public const USER_CAP_OVERRIDES_META_KEY = 'erp_omd_user_capability_overrides';
    public const USER_MENU_OVERRIDES_META_KEY = 'erp_omd_user_menu_visibility_overrides';

    /**
     * @param int $user_id
     * @param string $capability
     */
    public function can_user($user_id, $capability)
    {
        $user_id = (int) $user_id;
        $capability = sanitize_key((string) $capability);
        if ($user_id <= 0 || $capability === '') {
            return false;
        }

        $decision = $this->resolve_override((array) get_user_meta($user_id, self::USER_CAP_OVERRIDES_META_KEY, true), $capability);
        if ($decision === 'deny') {
            return false;
        }
        if ($decision === 'allow') {
            return true;
        }

        return user_can($user_id, $capability);
    }

    /**
     * @param int $user_id
     * @param string $page_slug
     */
    public function can_view_menu_page($user_id, $page_slug)
    {
        $user_id = (int) $user_id;
        $page_slug = sanitize_key((string) $page_slug);
        if ($user_id <= 0 || $page_slug === '') {
            return false;
        }

        $decision = $this->resolve_override((array) get_user_meta($user_id, self::USER_MENU_OVERRIDES_META_KEY, true), $page_slug);
        if ($decision === 'deny') {
            return false;
        }
        if ($decision === 'allow') {
            return true;
        }

        return true;
    }

    /**
     * @param array<string,string> $overrides
     * @param string $key
     * @return string
     */
    private function resolve_override(array $overrides, $key)
    {
        $raw = strtolower((string) ($overrides[$key] ?? ''));
        if ($raw === 'deny' || $raw === 'allow') {
            return $raw;
        }

        return 'inherit';
    }
}
