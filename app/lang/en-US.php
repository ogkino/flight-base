<?php
/**
 * Language pack: English (en-US)
 *
 * Convention: keep the exact same section/key structure as zh-CN.php (the base pack).
 * Missing keys automatically fall back to zh-CN via lang().
 */
return [

    'common' => [
        'confirm_title'         => 'Notice',
        'op_success'            => 'Operation successful',
        'op_failed'             => 'Operation failed',
        'load_failed'           => 'Failed to load',
        'network_error'         => 'Network error',
        'unauthorized'          => 'Unauthorized, please login first',
        'need_admin'            => 'Administrator privileges required',
        'no_permission'         => 'Permission denied',
        'please_login'          => 'Please login first',
        'admin_disabled'        => 'Administrator does not exist or has been disabled',
        'admin_expired'         => 'Administrator account has expired',
        'user_disabled'         => 'User does not exist or has been disabled',
        'user_expired'          => 'User account has expired',
        'token_invalid'         => 'Token is invalid or has expired',
        'copy_success'          => 'Copied successfully',
        'logout_confirm'        => 'Are you sure you want to log out?',
        'logout'                => 'Logout',
        'admin_default_name'    => 'Administrator',
        'status_normal'         => 'Enabled',
        'status_disabled'       => 'Disabled',
        'config_not_found'      => 'Configuration not found',
    ],

    'crud' => [
        'search'                => 'Search',
        'reset'                 => 'Reset',
        'add'                   => 'Add',
        'edit'                  => 'Edit',
        'delete'                => 'Delete',
        'submit'                => 'Submit',
        'actions'               => 'Actions',
        'preview'               => 'Preview',
        'please_select'         => 'Please select',
        'please_select_date'    => 'Please select a date',
        'please_select_icon'    => 'Please select an icon',
        'search_icon'           => 'Search icon...',
        'input_then_enter'      => 'Type and press Enter',
        'loading'               => 'Loading...',
        'select_icon_title'     => 'Select icon',
        'view_file'             => 'View file',
        'upload_success'        => 'Upload successful',
        'upload_failed'         => 'Upload failed',
        'image_upload_failed'   => 'Image upload failed',
        'editor_placeholder'    => 'Please enter content...',
        'confirm_delete'        => 'Are you sure you want to delete this?',
        'delete_success'        => 'Deleted successfully',
        'status_update_success' => 'Status updated successfully',
        'update_failed'         => 'Update failed',
        'password_too_short'    => 'Password must be at least 6 characters',
        'password_mismatch'     => 'Passwords do not match',
    ],

    // Note: menu / dashboard / change-password page text is no longer kept here.
    // It now lives directly in app/config/CrudConfig.php via the field-level
    // locale suffix convention (e.g. title_en), see docs/I18N.md.

    'auth' => [
        'captcha_required'            => 'Please enter the captcha code',
        'captcha_expired'              => 'Captcha has expired, please refresh',
        'captcha_error'                => 'Incorrect captcha code',
        'username_password_required' => 'Username and password are required',
        'admin_not_found'             => 'Administrator not found',
        'password_error'              => 'Incorrect password',
        'account_disabled'            => 'Account has been disabled',
        'account_expired'              => 'Account has expired',
        'login_success'                => 'Login successful',
        'logout_success'               => 'Logged out successfully',
        'fill_complete_info'           => 'Please fill in all required fields',
        'new_password_mismatch'        => 'The new passwords do not match',
        'new_password_too_short'       => 'New password must be at least 6 characters',
        'old_password_error'           => 'Current password is incorrect',
        'change_password_success'      => 'Password changed successfully, please log in again',
        'change_password_failed'       => 'Failed to change password',
    ],

    'permission' => [
        'list'   => 'View list',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'export' => 'Export',
        'custom' => 'Custom',
        'access' => 'Access',
    ],

];
