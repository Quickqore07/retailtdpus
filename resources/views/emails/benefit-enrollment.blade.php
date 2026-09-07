<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Required: Health Insurance Enrollment</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f4;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600; text-align: center;">
                                Action Required: Health Insurance Enrollment
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px; color: #333333; font-size: 16px; line-height: 1.6;">
                                Dear <strong>{{ $name }}</strong>,
                            </p>
                            
                            <p style="margin: 0 0 20px; color: #555555; font-size: 15px; line-height: 1.6;">
                                As part of our upcoming health insurance enrollment process, please complete your registration using the link below:
                            </p>
                            
                            <!-- CTA Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $link }}" style="display: inline-block; padding: 15px 40px; background-color: #667eea; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 5px; box-shadow: 0 2px 4px rgba(102,126,234,0.3);">
                                            Complete Benefits Enrollment
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 0 0 20px; color: #555555; font-size: 15px; line-height: 1.6;">
                                Your unique benefit code is: <strong>{{ $benefitCode }}</strong>.
                            </p>
                            <p style="margin: 20px 0 10px; color: #555555; font-size: 15px; line-height: 1.6;">
                                Please review the available plan options carefully and submit your enrollment selections by the specified deadline.
                            </p>
                            
                            <p style="margin: 20px 0; color: #555555; font-size: 15px; line-height: 1.6;">
                                If you have any questions or need assistance during the enrollment process, please contact HR/Contact Person.
                            </p>


                            
                            <p style="margin: 30px 0 10px; color: #555555; font-size: 15px; line-height: 1.6;">
                                Thank you for your prompt attention to this matter.
                            </p>
                            
                            <!-- Signature -->
                            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #e0e0e0;">
                                <p style="margin: 0 0 5px; color: #333333; font-size: 15px; line-height: 1.6;">
                                    Best regards,
                                </p>
                                <p style="margin: 0 0 5px; color: #667eea; font-size: 16px; font-weight: 600;">
                                    HR Department
                                </p>
                                <p style="margin: 0; color: #888888; font-size: 14px;">
                                    TDPUS
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px; background-color: #f8f9fa; border-radius: 0 0 8px 8px; text-align: center;">
                            <p style="margin: 0; color: #888888; font-size: 12px; line-height: 1.5;">
                                <strong>Employee ID:</strong> {{ $employeeId }}
                            </p>
                            <p style="margin: 10px 0 0; color: #999999; font-size: 11px; line-height: 1.5;">
                                This is an automated message. Please do not reply to this email.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
