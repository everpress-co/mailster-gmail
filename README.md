# Mailster Gmail Integration

Contributors: everpress, mailster, xaverb  
Tags: gmail, google, mailster, newsletter, email  
Requires at least: 6.0  
Tested up to: 6.5  
Stable tag: 1.3.1  
Requires PHP: 8.1  
License: GPLv2 or later

Deliver Mailster newsletters from a Gmail account using Google OAuth and the Gmail API.

## Description

**Mailster Gmail Integration** adds Gmail as a delivery method for the [Mailster Newsletter Plugin for WordPress](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=Gmail). Campaigns are sent through the [Gmail API](https://developers.google.com/workspace/gmail/api/guides/sending) from the Google account you authorize.

Use it when you want Mailster to send from a Gmail address instead of your host’s mail server. You connect a Google Cloud OAuth client, authorize the account once, and Mailster uses that mailbox for delivery.

> This plugin requires [Mailster](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=Gmail) **2.4.11** or newer, PHP **8.1** or newer, and a Google account. You create an OAuth Client ID in Google Cloud (this is not an API key). Newsletter content is sent to Google through the Gmail API. The plugin requests the `https://mail.google.com/` scope so it can send mail and read bounce messages in that mailbox. Gmail applies its own sending limits; Mailster’s guide notes that Gmail is a weak fit for large marketing lists.

### Send Mailster campaigns with Gmail

- **Gmail delivery method** — Choose Gmail under **Newsletter → Settings → Delivery**.
- **Google OAuth** — Enter the OAuth Client ID and Client Secret, then authorize the Google account. The settings screen shows the redirect URI to paste into the Google Cloud client.
- **Sending address** — After authorization, saving settings copies the connected Gmail address into Mailster’s From, Reply-To, and Bounce fields.
- **Gmail API sending** — Mailster messages are sent with `users.messages.send` for the authorized user. Mailster’s own DKIM signing is turned off for these sends because Gmail handles authentication.
- **Bounce handling** — The addon checks that Gmail mailbox for delivery failures and “please remove me” replies, then unsubscribes or bounces the matching Mailster subscriber and removes those messages.
- **Redirect URL filter** — Developers can change the OAuth redirect with the `mailster_gmail_redirect_url` filter.

### How it works

1. Install and activate **Mailster Gmail Integration** next to Mailster 2.4.11+.
2. In Google Cloud, enable the Gmail API and create an OAuth client (Web application). Add the redirect URI shown in Mailster’s Gmail settings.
3. Open **Newsletter → Settings → Delivery**, select **Gmail**, paste the Client ID and Client Secret, and save.
4. Click **Authorize**, allow access in Google, and return to Mailster.
5. Send a test email, then send Mailster campaigns through Gmail.

Setup walkthrough: [Send your newsletters via Gmail](https://kb.mailster.co/send-your-newsletters-via-gmail?utm_campaign=wporg&utm_source=Gmail&utm_medium=readme).

## Installation

1. Upload the entire `mailster-gmail` folder to the `/wp-content/plugins/` directory, or install it from the WordPress plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Make sure [Mailster](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=Gmail) 2.4.11 or newer is installed and active.
4. In [Google Cloud](https://console.cloud.google.com/), enable the Gmail API, configure the OAuth consent screen, and create a Web application OAuth client. Add the Authorized redirect URI from Mailster’s Gmail settings.
5. Go to **Newsletter → Settings → Delivery**, open the **Gmail** tab, enter the Client ID and Client Secret, and save.
6. Click **Authorize**, grant access to the Google account, and send a test email.

## Frequently Asked Questions

### Does this plugin work without Mailster?

No. This is a delivery addon for the [Mailster Newsletter Plugin](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=Gmail). It requires Mailster **2.4.11** or newer.

### Do I paste a Gmail API key?

No. Gmail delivery uses a Google OAuth **Client ID** and **Client Secret** from a Google Cloud project, then an **Authorize** step so Mailster can send as that account. The setup guide walks through the OAuth client and redirect URI: [Send your newsletters via Gmail](https://kb.mailster.co/send-your-newsletters-via-gmail?utm_campaign=wporg&utm_source=Gmail&utm_medium=readme).

### Which address do newsletters come from?

The Gmail address you authorize. When you save delivery settings while connected, Mailster’s From, Reply-To, and Bounce addresses are set to that account’s email address.

### Is email sent to Google?

Yes. With Gmail selected as the delivery method, Mailster sends the message through the Gmail API. The OAuth consent screen requests `https://mail.google.com/` so the addon can send mail and look in that mailbox for bounces.

### Does it handle bounces?

Yes, for mail sent with this addon. It searches the connected Gmail inbox and spam for mail-delivery failures and list-unsubscribe replies, matches Mailster’s campaign headers, updates the subscriber, and deletes those messages from the mailbox.

### What if Google asks me to reconnect?

If Gmail rejects the saved token, the addon clears the connection and shows a notice. Open **Newsletter → Settings → Delivery**, check the Gmail tab, and authorize again.

### Can I change the OAuth redirect URL?

The settings screen shows the redirect URI to register in Google Cloud. Developers can replace it with the `mailster_gmail_redirect_url` filter. The value in Google Cloud must match the URL Mailster uses.

## Screenshots

### 1. Option Interface

![Option Interface.](https://ps.w.org/mailster-gmail/assets/screenshot-1.png)

## Changelog

### Unreleased

- changed: requires PHP 8.1+

### 1.3.1

- fixed: composer packages with PHP 8.0+

### 1.3.0

- fixed: issue with third party library on PHP 8.0+
- added: `mailster_gmail_redirect_url` filter for redirect URL
- requires now PHP 7.4+

### 1.2

- Fixed some typo and updated method

### 1.1

- Update for Mailster 3.0

### 1.0

- initial release
