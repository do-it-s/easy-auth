<?php

return [
    'heading' => 'This account cannot be used on this device',
    'description' => "Your password was correct, but this device doesn't match the one this account is bound to. This can't be recovered as-is — delete the account and ask an administrator for a new invitation to start over. Your email address will be free to use again once it's deleted.",
    'confirm_label' => 'Type your name (:name) to confirm deletion',
    'delete_button' => 'Delete this account',
    'name_mismatch' => 'The name you entered does not match.',
    'deleted_heading' => 'Account deleted',
    'deleted' => 'Ask an administrator for a new invitation to register again.',

    'mail_subject' => 'Sign-in attempt from an unrecognized device',
    'mail_intro' => "We noticed a successful password sign-in attempt for your account from a device we don't recognize. Because this app also checks the device, the sign-in did not go through.",
    'mail_safe_notice' => "If this wasn't you, no action is needed — your account is safe.",
    'mail_action' => 'Delete this account',
    'mail_expires' => "If you'd like to start over on this device, use the button above to delete this account (this link expires in 15 minutes), then ask an administrator for a new invitation.",
];
