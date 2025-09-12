<p> Hello {{ $email }},</p>
<p>You have requested a password reset. Please click the link below to reset your password:</p> 
<a href="{{ url('/reset-password?token=' . $token) }}">Reset Password</a>
<p>This link will expire soon.</p>  
<p>If you did not request a password reset, please ignore this email.</p>
<p>Thank you,</p>