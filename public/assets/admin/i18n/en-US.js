(function (window) {
    'use strict';

    var part1 = String.raw`
Group ID
Group name
Users
Nodes
Actions
Edit
Delete
Permission groups
 Add permission group
Country
System configuration
Site
Site name
Displayed wherever the site name is needed.
Enter the site name
Site description
Displayed wherever the site description is needed.
Enter the site description
Site URL
The current public site URL, used in emails and wherever a URL is required.
Enter the site URL without a trailing /
Force HTTPS
Enable when the site does not use HTTPS but the CDN or reverse proxy forces HTTPS.
Displayed wherever a LOGO is needed.
Enter the LOGO URL without a trailing /
Subscription URL
Used for subscriptions. Leave empty to use the site URL; separate multiple random URLs with commas.
Enter subscription URLs without a trailing /; separate domains with commas
Subscription path
Used for subscriptions. Leave empty for /api/v1/client/subscribe, or set a custom path.
Terms of Service (TOS) URL
Links to the Terms of Service (TOS)
Enter the Terms URL without a trailing /
Stop new registrations
When enabled, nobody can register.
Registration trial
Select a trial plan; add one under Plan Management if no option is available.
Select a trial plan
Off
Trial duration (hours)
Please enter
Currency unit
Used for display only; changing it updates the currency unit throughout the system.
Currency symbol
Security
Email verification
When enabled, users must verify their email.
Disallow Gmail aliases
When enabled, Gmail aliases cannot be used to register.
Safe mode
When enabled, domains bound to this site other than the configured site URL receive a 403 response.
Admin path
Administration path; changing it replaces the existing admin path
Allowed email suffixes
When enabled, only email suffixes in the list can register.
Allowed suffixes
Separate with commas, for example: qq.com,gmail.com.
Enter suffix domains separated with commas, e.g. qq.com,gmail.com
Bot protection
When enabled, Google reCAPTCHA is used to prevent bots.
Secret key
Secret key issued by Google reCAPTCHA.
Site key
Site key issued by Google reCAPTCHA.
IP registration limit
When enabled, an IP is blocked after reaching the registration limit. IP detection may be affected by a CDN or proxy.
Count
Apply the penalty after this many registrations.
Penalty duration (minutes)
Registration is available again after the penalty expires.
Brute-force protection
When enabled, an account is restricted after too many failed login attempts.
Apply the penalty after this many failures.
Login is available again after the penalty expires.
Subscription
Allow users to change subscriptions
When enabled, users can change their subscription plan.
Monthly traffic reset method
Global traffic reset method, defaulting to the first of each month. A plan can override it.
Select a subscription reset method
First day of each month
Reset monthly
Never reset
January 1 each year
Reset annually
Enable plan credit
When enabled, the system credits the value of the old plan when a user changes plans; see the documentation.
Allow an early traffic cycle
When traffic is exhausted, users may trade subscription time for a reset. Monthly mode deducts the remaining cycle; first-day mode deducts 30 days.
Event when purchasing a new subscription
Runs this task after a new subscription purchase completes.
Select an event
Do nothing
Reset user traffic
Event when renewing a subscription
Runs this task after a subscription renewal completes.
Event when changing a subscription
Runs this task after a subscription change completes.
Show subscription information in subscriptions
When enabled, plan information is included when a user retrieves subscription nodes.
Subscription link validity mode
Validity period after a user retrieves a subscription link.
Please select
Permanent
One-time
Time-limited
Subscription link lifetime (minutes)
The subscription link expires after this time.
Top-up
Top-up reward
Reward granted after topping up a specified amount.
Enter top-up amount:reward amount, separated by commas\nExample: 50:18,100:38, 200:88
Ticket
Ticket settings
Select the ticket availability.
Tickets fully open
Only users with paid orders
Tickets fully disabled
Invitations & commission
Require invitations
When enabled, only invited users can register.
Invitation commission percentage
Default global commission rate; a custom rate can be set per user.
Maximum invitation codes per user
Invitation codes never expire
When enabled, an invitation code remains valid after use; otherwise it expires after one use.
Commission on first payment only
When enabled, commission is created only on the invitee's first payment; this can be configured per user.
Automatic commission confirmation
When enabled, commission is confirmed automatically three days after the order completes.
Withdrawal threshold (CNY)
Withdrawals below the threshold are not submitted.
Withdrawal methods
Supported withdrawal methods.
Enter methods separated by commas, e.g. Alipay,USDT,PayPal
Disable withdrawals
When disabled, users cannot request withdrawals and invitation commission goes directly to their balance.
Three-level distribution
When enabled, commission is split across the three configured rates; the total must not exceed 100%.
Level-one inviter rate
Enter a percentage, e.g. 50
Level-two inviter rate
Enter a percentage, e.g. 30
Level-three inviter rate
Enter a percentage, e.g. 20
Appearance
If the V2Board admin frontend and backend are deployed separately, settings on this page have no effect. Learn more
Separate frontend/backend
Sidebar style
Light
Dark
Header style
Theme color
Default
Black
Dark blue
Mint green
Background
Displayed on the admin login page.
Node
Node API URL
Dedicated one-click connection URL for v2node nodes.
Communication key
Key used between V2Board and nodes to prevent others from obtaining data.
Node pull polling interval
How often nodes retrieve data from the panel.
seconds
Node push polling interval
How often nodes push data to the panel.
Minimum user traffic reporting threshold
Each push reports only users whose accumulated traffic exceeds the threshold; unreported traffic keeps accumulating
Minimum device-count traffic threshold
Each push counts online device IPs only when traffic exceeds the threshold
Use relaxed global device limits
When enabled, the same IP using multiple nodes counts as one device
Email
After changing these settings, restart the queue service. These settings take priority over email settings in .env.
SMTP server address
Service address supplied by the email provider
SMTP server port
Common ports are 25, 465 and 587
SMTP encryption
Port 465 generally uses SSL and port 587 generally uses TLS
SMTP account
Account supplied by the email provider
SMTP password
Password supplied by the email provider
Sender address
Sender address supplied by the email provider
Email template
See the documentation to customize email templates
Send test email
The email is sent to the currently signed-in administrator
Bot token
Enter the token supplied by BotFather.
Configure Webhook
Configure the bot Webhook; Telegram notifications cannot be received without it.
One-click setup
Enable bot notifications
When enabled, the bot sends basic notifications to administrators and users linked to Telegram.
`.slice(1, -1).split('\n').map(function (value) { return value.replace(/\\n/g, '\n'); });

    var part2 = String.raw`
Group address
Displayed to users or used where required after it is entered.
Version management and updates for first-party client apps
Windows version and download URL
macOS version and download URL
Android version and download URL
User management
Their traffic records
Enter a reply to the ticket...
Path
Edit node
Create node
Node name
Enter the node name
Rate
Enter the node rate
Node tags
Enter a tag and press Enter
Permission group
Add permission group
Select a permission group
Node address
Address or IP
Connection port
User connection port
Service port
Open server port
Encryption algorithm
Obfuscation
None
Parent node
https://docs.v2board.com/use/node.html#parent-and-child-nodes
More help
Route group
Select a route group
Cancel
Submit
Visible
Title
Created at
Announcement management
 Add announcement
Edit announcement
Create announcement
Enter the announcement title
Announcement content
Enter the announcement content
Announcement tag
Image URL
Enter the image URL
Queue monitor
Overview
Current jobs
Processed in the last hour
Errors in the last 7 days
Status
Running
Not started
Current job details
Queue name
Order queue
Email queue
Bulk email queue
Telegram message queue
Statistics queue
Traffic consumption queue
Jobs
Tasks
Elapsed time
Today
Now
Back to today
OK
Select time
Select date
Select week
Clear
Month
Year
Previous month (Page Up)
Next month (Page Down)
Select month
Select year
Select decade
YYYY
Day D
MM/DD/YYYY
MM/DD/YYYY HH:mm:ss
Previous year (Control + Left)
Next year (Control + Right)
Previous decade
Next decade
Previous century
Next century
items/page
Go to
page
Previous page
Next page
Previous 5 pages
Next 5 pages
Previous 3 pages
Next 3 pages
Enabled
Coupon name
Type
Amount
Percentage
Coupon code
Copied
Uses remaining
Unlimited
Valid until
Warning
Are you sure you want to delete this item?
Coupon management
 Add coupon
Edit coupon
Create coupon
Name
Enter the coupon name
Custom coupon code
Custom coupon code (leave empty to generate)
Discount details
Fixed-amount discount
Percentage discount
Enter a value
Coupon validity
Maximum uses
Limit total uses; the coupon stops working when exhausted (empty means unlimited)
Uses per user
Limit uses per user (empty means unlimited)
Specific subscriptions
Restrict the discount to selected subscriptions (empty means unrestricted)
Specific periods
Restrict the discount to selected periods (empty means unrestricted)
Quantity to generate
Enter a quantity for bulk generation
Create user
Generate
Email
Account (leave empty for bulk generation)
Domain
Password
Leave empty to use the email as the password
Expiration time
Select an expiration date; empty means no expiration
Subscription plan
Select a subscription plan for the user
For bulk generation, enter the quantity
Low
Medium
High
Subject
Ticket priority
Ticket status
Replied
Awaiting reply
Closed
Last reply
View
Ticket management
Open
Enter an email to search
Sign in to the Admin Center
Sign in
Forgot password
Run this command in the site directory to recover the password
php artisan reset:password administrator-email
Got it
Date
Upload
Download
Traffic records
Reminder
Are you sure you want to ban this user?
Are you sure you want to delete this user?
Reset security information
Are you sure you want to reset
their security information?
Delete user
Are you sure you want to delete
this user's information?
Last online
Never online
Banned
Active
Permission group
Used (GB)
Traffic (GB)
Devices
Long-term
Balance
Commission
Joined at
 Edit
 Assign order
 Copy subscription URL
 Reset UUID and subscription URL
 Their orders
`.slice(1, -1).split('\n');

    var part3 = String.raw`
 Their invitations
 Their traffic records
 Delete user
Actions
Tip: filter users first, then run an action on the filtered users.
Fuzzy
User ID
No subscription
Traffic
Account status
Inviter email
Inviter ID
Notes
Administrator
Yes
No
 Filter
 Export CSV
 Send email
 Bulk ban
 Bulk delete
Sending
Edit payment method
Add payment method
Save
Add
Display name
Used for display in the user frontend
Icon URL (optional)
Used for display in the user frontend (https://x.com/icon.svg)
Custom notification domain (optional)
Gateway notifications are sent to this domain (https://x.com)
Percentage fee (optional)
Add a fee based on the order amount
Fixed fee (optional)
Interface file
Payment interface
Notification URL
The payment gateway sends data to this address; allow it through the firewall.
Payment configuration
 Add payment method
Value is required
Filter
Search text is required
Condition
Field name
Search text
Select a value
Value
 Add condition
Reset
Search
Exporting
Queued for execution
Reset successfully
Deleted successfully
Edit subscription
Create subscription
Plan name
Enter the plan name
Plan description
Enter the plan description; HTML is supported
Price settings
Leave a price empty to make that period unavailable
Monthly
Quarterly
Half-year
Yearly
Two years
Three years
One-time
Reset package
Plan traffic
Enter the plan traffic
Device limit
Empty means unlimited
Traffic reset method
Follow system settings
Maximum users
Speed limit
Apply changed traffic, speed limit and permission group to users on this plan
Force update users
Sort order
Sales status
Renewal
Whether existing customers may renew while the subscription is not on sale
Statistics
Semiannual
 Delete
Subscription management
 Add subscription
Saved successfully
Edit knowledge article
Add knowledge article
Enter the article title
Category
Enter a category; articles are grouped automatically
Language
Select the article language
Content
Article ID
Updated at
Knowledge base management
Add
Assign order
User email
Enter the user's email
Select a subscription
Select a period
Payment amount
Enter the amount to pay
Send email
Recipients
Filter users
All users
Enter the email subject
Message
Enter the email content
Select time
Select date
Start date
End date
OK
Filter
Select this page
Invert this page
Expand row
Close row
Got it
Enter search text
items
Uploading file
Delete file
Upload error
Preview file
Download file
No data
Icon
Copy
Expand
Back
Are you sure you want to clear all content?
Clear all
Bold
Italic
Underline
Strikethrough
Unordered list
Ordered list
Quote
Line break
Inline code
Code block
Table
Image
Link
Undo
Redo
Fullscreen
Exit fullscreen
Editor only
Preview only
Editor and preview
Actual input when Tab is pressed
Tab
Space
`.slice(1, -1).split('\n');

    var part4 = String.raw`
# Order number
127.0.0.1 (single match)\n10.0.0.0/8 (range match)\ngeoip:cn (predefined list match)
DNS server
DNS server address
DNS server list
DNS request
DNS resolver provider
ECH Config (client configuration)
ECH Key (server private key)
ECH Server Name (decoy domain/outer SNI)
HTTP obfuscation
HTTP request
HYSTERIA version
REALITY is required and must match the backend
REALITY target address; defaults to SNI
REALITY target port; defaults to 443
TLS fingerprint defaults to Chrome
XTLS flow control algorithm
Xray outbound configuration
example.com (keyword match)\ndomain:example.com (subdomain match)\ngeosite:netflix (predefined domain list)
Webhook configured successfully
✓ Cloudflare manages ECH; keys are managed automatically, clients obtain configuration from DNS, and no server configuration is required
Enter 0 for a one-time plan
One-click install command
Commission payouts last month
Revenue last month
Upload bandwidth
Download bandwidth
Leave blank if unused
Not supported
Exclusive discount rate
Theme settings
Theme configuration
Format: CF_DNS_API_TOKEN=xxxxxxx; separate multiple entries with commas
Format: cloudflare
Users
Revenue today
User traffic ranking today
Node traffic ranking today
Dashboard
Discount amount
Transport protocol
Invalid transport protocol configuration format
Balance payment
Commission status
Commission status
Commission amount
Used traffic is multiplied by this rate
Self-signed certificates require Allow insecure for users to connect
Save order
Trusted XFF header (for obtaining the real IP)
Rate
Allow insecure
Allow insecure
Redeem subscription plan
Create group
Create route
Action
Encryption method
Match
Match value
Match count
Detailed protocol configuration
Protocol filter
Gift card code
Reference
Outgoing encryption:
Outgoing server:
Outgoing username:
Outgoing port:
Issuing
Send failed
Sent successfully
Change
Period
Callback transaction number
Online users
Address
Address or IP defaults to 0.0.0.0
Domain
Domains in this list are resolved by this server first, one per line
Domain filter
See reference
Add plan traffic
Extend subscription
Add account balance
 Copy
 days
days
Failure reason:
Plan
When the V2board frontend and backend are deployed separately, theme configuration does not take effect. Learn more
Enter to change the password
Security
Real-time registrations
Actual payout
Enable 0-RTT on the client
Issued
Canceled
Completed
Credited
Paid
Upload used
Download used
Rejected
Common headers: X-Forwarded-For CF-Connecting-IP X-Real-IP
Enable
Activating
Current theme
The queue service is currently unhealthy and may disrupt operations.
Used to verify the certificate when the node address and certificate do not match
Pending payment
Pending confirmation
Awaiting reply
Recurring rebate
Required
Mark as
After marking as [Paid], the system activates and completes the order
After marking as [Valid], the system issues it to the user and completes the order
Credit amount
Drag to reorder
Congestion control algorithm
Resolve with the specified DNS server
Use the specified outbound server (IP targets)
Use the specified outbound server (domain targets)
Metrics
Referral commission
Referral rebate rate
Referral rebate type
Search
Supported
Recipient address:
Value
Packet relay mode
Create gift card
New purchase
Duration
 Unused or server reporting error
Invalid
Default when no rule matches
No certificate (disable TLS)
User traffic ranking yesterday
Node traffic ranking yesterday
Staff member
Supports TLS
Administrator
Visibility
Maximum allowed time
There are
Valid
Server
Server Name Indication (SNI)
Server group
Server upload bandwidth; leave blank or enter 0 to use BBR
Server download bandwidth; leave blank or enter 0 to use BBR
Unpaid
 Not running
Revenue this month
New users this month
 tickets awaiting action
 rules
View people invited by this user
Traffic add-on
Traffic reset add-on
Obfuscation password obfsParam
Obfuscation password obfs_password
Obfuscation method obfs
Add gift card
 Add order
 Add route
Determined by the server reporting frequency
Activate theme
Users
Leave blank to use the default 100-111-1111.75-0-111.50-0-3333
Leave blank to generate automatically in /etc/v2node/
Leave blank to generate automatically
Leave blank to generate automatically; replace it for post-quantum encryption
Log out
Listen address
Gift card validity period
Gift card management
Gift card type
Block access (IP target)
Block access (protocol)
Block access (domain target)
Block access (port target)
Disable SNI
Handle now
Port
 commissions awaiting confirmation
Simplified Chinese
System settings
Group name
Renewal
Edit TLS configuration
Edit encryption configuration
Edit protocol configuration
Edit padding scheme
Edit security configuration
Edit order
Edit gift card
Edit group
Edit route
Edit configuration
Custom SNI
Custom gift card code
Custom gift card code (leave blank to generate randomly)
Custom default outbound
Self-signed
Node
Node ID
Node protocol
Node order has not been saved. Leave this page?
Node management
Order details
Order number
Order period
Order status
Order status
Order management
Settings
This user will always receive this discount when purchasing any subscription
Certificate public key file path (Cert File Path)
Certificate mode (Cert Mode)
Certificate private key file path (Key File Path)
Leave notes here..
Request failed
Enter the DNS server address
Enter the exclusive discount rate
Enter a note
Enter the referral rebate rate (leave blank to follow the site setting)
Enter traffic
Enter the DNS server address to use for resolution
Enter the gift card name
Enter the group name
Enter the connection address
Enter the inviter's email
Enter an email address
Select an action
Finance
Account status
Router
Route management
Search for any keyword
 Running normally
Connection address
Refund amount
Select ECH mode
Select an XTLS flow control algorithm
Select a transport protocol
Select an encryption method
Inviter
Configuration
Reset plan traffic
Non-NAT uses the same connection port
First-purchase rebate
`.slice(1, -1).split('\n').map(function (value) { return value.replace(/\\n/g, '\n'); });

    window.V2BoardAdminI18nRegisterChinese('en-US', part1.concat(part2, part3, part4), Object.create(null));
})(window);
