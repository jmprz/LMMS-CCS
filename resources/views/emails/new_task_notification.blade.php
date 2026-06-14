<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Academic Task Assigned</title>
</head>
<body style="background-color: #f3f4f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 40px 20px; -webkit-font-smoothing: antialiased;">

    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 520px; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        
        <tr>
           <td style="background-color: #383838; padding: 40px 32px; text-align: center;">
                <h1 style="color: #ffffff; font-size: 24px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">
                    Learning and Monitoring Management System
                </h1>
                <div style="width: 60px; height: 2px; background-color: #ffffff; margin: 12px auto; border-radius: 9999px;"></div>
                <p style="color: #ffffff; font-size: 11px; font-weight: 600; text-transform: uppercase; margin: 0; letter-spacing: 0.15em;">
                    College of Computing Studies
                </p>
            </td>
        </tr>

        <tr>
            <td style="padding: 40px 32px;">
                <h2 style="color: #111827; font-size: 20px; font-weight: 900; margin: 0 0 12px 0; text-transform: uppercase; tracking-spacing: -0.5px;">
                    New Task Assigned
                </h2>
                <p style="color: #4b5563; font-size: 14px; line-height: 1.6; font-weight: 500; margin: 0 0 24px 0;">
                  Hi, {{ $student->first_name }}! Your professor just posted a new task for your class. Check out the instructions below to see what you need to do.
                </p>

                <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                    <span style="display: block; color: #9ca3af; font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 2px;">Subject / Class Class</span>
                    <span style="display: block; color: #111827; font-size: 14px; font-weight: 700; margin-bottom: 12px;">
                        {{ $task->labSession->subject_name ?? 'Assigned Laboratory Class' }}
                    </span>

                    <span style="display: block; color: #9ca3af; font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 2px;">Task Title</span>
                    <span style="display: block; color: #111827; font-size: 15px; font-weight: 700; margin-bottom: 12px;">{{ $task->title }}</span>
                    
                    @if(!empty($task->description))
                        <span style="display: block; color: #9ca3af; font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 2px;">Overview / Instructions</span>
                        <p style="color: #4b5563; font-size: 13px; line-height: 1.5; margin: 0 0 12px 0;">{{ \Illuminate\Support\Str::limit($task->description, 160) }}</p>
                    @endif

                    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-top: 8px; border-top: 1px solid #e5e7eb; padding-top: 12px;">
                        <tr>
                            <td>
                                <span style="display: block; color: #9ca3af; font-size: 10px; font-weight: 800; text-transform: uppercase;">Max Evaluation</span>
                                <span style="color: #111827; font-size: 13px; font-weight: 700;">{{ $task->points }} Points</span>
                            </td>
                            <td style="text-align: right;">
                                <span style="display: block; color: #9ca3af; font-size: 10px; font-weight: 800; text-transform: uppercase;">Submission Deadline</span>
                                <span style="color: #dc2626; font-size: 13px; font-weight: 700;">
                                    {{ \Carbon\Carbon::parse($task->deadline)->format('M d, Y - h:i A') }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="background-color: #fff5f5; border: 1px solid #fee2e2; border-radius: 12px; padding: 18px; text-align: center;">
                    <span style="display: block; color: #dc2626; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                        ⚠️ School Laboratory Access Only
                    </span>
                    <p style="color: #991b1b; font-size: 12px; line-height: 1.5; font-weight: 600; margin: 0;">
                        This task cannot be accessed via home networks, personal internet routers, or mobile devices. You are required to review instructions and submit your outputs exclusively on designated school laboratory during your officially scheduled class hours.
                    </p>
                </div>
            </td>
        </tr>

        <tr>
            <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 24px 32px; text-align: center;">
                <p style="color: #9ca3af; font-size: 11px; line-height: 1.5; font-weight: 500; margin: 0;">
                    This tracking alert transaction message is distributed automatically by LMMS.<br>
                    Please do not reply directly to this outbound mailbox.
                </p>
            </td>
        </tr>
    </table>

</body>
</html>