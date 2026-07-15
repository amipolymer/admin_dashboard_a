````markdown
# Domain, DNS, Website Hosting & Third-Party Services Management Guide

## Overview

This document explains how to manage a company domain when using multiple services such as:

- Shopify (Website)
- Microsoft 365 / Google Workspace (Email)
- Salesforce (CRM)
- Mailchimp
- HubSpot
- Zendesk
- Other SaaS applications

The objective is to keep every service independent so migrating one service (such as moving the website to Shopify) does not affect others.

---

# 1. Core Concepts

## Domain

Your domain is your company's internet identity.

Example:

```
company.com
```

The domain itself does not host anything.

---

## DNS (Domain Name System)

DNS tells internet users where each service is located.

Think of DNS as a directory.

Example:

```
company.com
        │
        ▼
      DNS
        │
 ┌──────┼──────────────┐
 │      │              │
Website Email      Applications
```

DNS controls:

- Website
- Email
- CRM
- Verification records
- Security records
- Third-party integrations

---

## Website Hosting

This is where your website actually lives.

Examples:

- Shopify
- Shared Hosting
- AWS
- Azure
- Vercel
- Netlify

Only the website should depend on the hosting provider.

---

## Email Hosting

Email should be completely separate from website hosting.

Examples:

- Microsoft 365
- Google Workspace
- Zoho Mail

Example:

```
user@company.com
```

The email service is configured through DNS (MX records).

---

## Third-Party Applications

Examples:

- Salesforce
- HubSpot
- Mailchimp
- Zendesk
- Google Search Console
- Adobe
- Slack
- Zoom

These usually require:

- TXT records
- CNAME records
- Verification records

---

# 2. Current Shared Hosting Architecture

Many companies start like this:

```
               Shared Hosting
                     │
       ┌─────────────┼────────────┐
       │             │            │
   Website        Email        DNS
```

Advantages

- Easy to start
- Low cost

Disadvantages

- Website and email are tightly coupled.
- Migration becomes difficult.
- Higher risk during DNS changes.
- Limited flexibility.

---

# 3. Recommended Architecture

```
                Domain Registrar
                       │
                       ▼
                 DNS Provider
                 (Cloudflare)
                       │
        ┌──────────────┼───────────────────┐
        │              │                   │
     Shopify      Microsoft 365       Salesforce
      Website         Email               CRM
                       │
             Mailchimp / HubSpot /
             Google Workspace /
             Other SaaS Applications
```

Each service is independent.

---

# 4. Responsibilities

## Domain Registrar

Responsible for:

- Domain registration
- Name servers

Examples:

- GoDaddy
- Namecheap
- Porkbun
- Cloudflare Registrar

---

## DNS Provider

Responsible for DNS records only.

Recommended:

- Cloudflare

Benefits:

- Easy management
- Fast propagation
- Security
- DDoS protection
- SSL support

---

## Website Provider

Responsible only for the website.

Example:

```
company.com
www.company.com
```

If using Shopify:

```
A Record

@
23.227.38.65

CNAME

www
shops.myshopify.com
```

---

## Email Provider

Responsible only for email.

Example DNS:

```
MX
TXT (SPF)
CNAME (DKIM)
TXT (DMARC)
```

Changing website hosting should never affect these records.

---

# 5. Common DNS Record Types

## A Record

Maps a hostname to an IPv4 address.

Example:

```
@
23.227.38.65
```

Purpose:

Website hosting

---

## AAAA Record

Maps hostname to IPv6.

Example:

```
@
2606:4700:....
```

---

## CNAME Record

Points one hostname to another.

Example:

```
www
shops.myshopify.com
```

---

## MX Record

Mail exchange server.

Example:

```
Priority 10

company-com.mail.protection.outlook.com
```

Without MX records:

- Email will stop working.

---

## TXT Record

Stores text information.

Used for:

- SPF
- Verification
- Domain ownership
- Google Search Console
- Mailchimp
- Salesforce

Example:

```
v=spf1 include:spf.protection.outlook.com -all
```

---

## DKIM

Email authentication.

Usually implemented using CNAME records.

Purpose:

Prevent email spoofing.

---

## DMARC

Email security policy.

Example:

```
v=DMARC1; p=quarantine;
```

---

## SRV Records

Used by some services like:

- Microsoft Teams
- SIP
- VoIP

---

# 6. Example DNS Inventory

| Type | Host | Purpose | Service |
|------|------|---------|----------|
| A | @ | Website | Shopify |
| CNAME | www | Website | Shopify |
| MX | @ | Email | Microsoft 365 |
| TXT | @ | SPF | Microsoft 365 |
| CNAME | selector1 | DKIM | Microsoft 365 |
| CNAME | selector2 | DKIM | Microsoft 365 |
| TXT | _dmarc | DMARC | Microsoft 365 |
| TXT | verify | Verification | Google |
| CNAME | salesforce | CRM | Salesforce |
| TXT | mailchimp | Verification | Mailchimp |

---

# 7. Migrating from Shared Hosting to Shopify

## Before Migration

✔ Export all DNS records.

✔ Take screenshots.

✔ Lower TTL if necessary.

✔ Identify:

- Website records
- Email records
- CRM records
- Verification records

---

## During Migration

Only update:

```
A Record

@
```

and

```
CNAME

www
```

Do NOT delete:

- MX
- TXT
- DKIM
- DMARC
- Salesforce records
- Mailchimp records
- Google records

---

## After Migration

Verify:

- Website loads
- HTTPS works
- Email send
- Email receive
- Salesforce
- HubSpot
- Mailchimp
- Contact forms
- Login pages

---

# 8. What Can Break?

If all DNS records are deleted:

❌ Email

❌ Microsoft 365

❌ Google Workspace

❌ Salesforce

❌ HubSpot

❌ Mailchimp

❌ Domain verification

❌ SSL validation

❌ API integrations

---

# 9. Best Practices

## Keep Services Separate

| Service | Recommended Provider |
|----------|----------------------|
| Domain | Any Registrar |
| DNS | Cloudflare |
| Website | Shopify |
| Email | Microsoft 365 / Google Workspace |
| CRM | Salesforce |
| CDN | Cloudflare |

---

## Maintain a DNS Inventory

Document every DNS record.

Example:

| Service | Record Type | Host | Purpose | Owner |
|----------|-------------|------|----------|-------|
| Shopify | A | @ | Website | IT |
| Shopify | CNAME | www | Website | IT |
| Microsoft 365 | MX | @ | Email | IT |
| Microsoft 365 | TXT | @ | SPF | IT |
| Salesforce | CNAME | salesforce | CRM | CRM Team |
| Google | TXT | @ | Verification | Marketing |

---

## Backup DNS

Always export:

- DNS Zone File
- Screenshots
- Provider configuration

Before making changes.

---

## Reduce TTL Before Migration

Example:

```
Current TTL

86400

↓

300
```

This speeds up DNS propagation during migration.

---

# 10. Migration Checklist

## Before

- [ ] Export DNS records
- [ ] Backup DNS
- [ ] Lower TTL
- [ ] Verify email configuration
- [ ] Verify third-party applications

---

## During

- [ ] Update Shopify A Record
- [ ] Update Shopify CNAME
- [ ] Keep MX records
- [ ] Keep TXT records
- [ ] Keep DKIM
- [ ] Keep DMARC
- [ ] Keep Salesforce records

---

## After

- [ ] Website working
- [ ] HTTPS working
- [ ] Email sending
- [ ] Email receiving
- [ ] Salesforce working
- [ ] HubSpot working
- [ ] Mailchimp working
- [ ] Forms working
- [ ] DNS propagation completed

---

# 11. Key Takeaways

- A domain is not the website.
- DNS is the traffic controller for all internet services.
- Website hosting and email hosting should be independent.
- Shopify migration only requires updating website DNS records.
- Email (MX, SPF, DKIM, DMARC) should remain unchanged during website migration.
- Maintain a documented DNS inventory to simplify future changes and reduce risk.
- Separating DNS, website hosting, email, and business applications provides greater flexibility, easier maintenance, and safer migrations.

---

# Recommended Enterprise Architecture

```
                    company.com
                          │
                 Domain Registrar
                          │
                    Name Servers
                          │
                     Cloudflare DNS
                          │
      ┌───────────────────┼─────────────────────┐
      │                   │                     │
      ▼                   ▼                     ▼
   Shopify          Microsoft 365          Salesforce
   Website              Email                 CRM
      │                   │
      ├──────────────┐    │
      ▼              ▼    ▼
 Mailchimp      HubSpot  Google Workspace
      │
      ▼
 Other SaaS Applications
```

This architecture ensures that changing one service (e.g., website hosting) has minimal impact on the others.
````
