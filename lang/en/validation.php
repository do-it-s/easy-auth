<?php

return [
    'name.required' => 'The name field is required.',
    'name.string' => 'The name field must be a string.',
    'name.max' => 'The name field must not be greater than 255 characters.',

    'email.required' => 'The email field is required.',
    'email.string' => 'The email field must be a string.',
    'email.email' => 'The email field must be a valid email address.',
    'email.max' => 'The email field must not be greater than 255 characters.',
    'email.unique' => 'The email has already been taken.',

    'password.required' => 'The password field is required.',
    'password.string' => 'The password field must be a string.',
    'password.confirmed' => 'The password confirmation does not match.',
    'password.min' => 'The password field must be at least :min characters.',

    'token.required' => 'The token field is required.',
    'token.string' => 'The token field must be a string.',

    'role.required' => 'The role field is required.',
    'role.in' => 'The selected role is invalid.',

    'label.string' => 'The label field must be a string.',
    'label.max' => 'The label field must not be greater than 255 characters.',

    'expires_at.date' => 'The expires at field must be a valid date.',
    'expires_at.after' => 'The expires at field must be a date after now.',

    'member_invites_enabled.boolean' => 'The member invites enabled field must be true or false.',
];
