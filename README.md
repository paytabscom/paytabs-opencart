# PayTabs OpenCart

The official **OpenCart** Plugin for PayTabs (PT-2).

Supports OpenCart **4.x**

Supports OpenCart **2.3** & **3.x**, release version (v3.x.x)

---

## Installation

### OpenCart 4.x

#### Install using OpenCart Admin panel

1. Download the latest release of the plugin [OpenCart 4.x](https://github.com/paytabscom/paytabs-opencart/releases/download/latest/paytabs.ocmod.zip)
2. Go to `"OpenCart admin panel" >> Extensions >> Installer`
3. Click `Upload`
4. Select the downloaded zip file (`paytabs.ocmod.zip`)
5. Wait until the upload *Progress* success
6. On the plugin row `PayTabs - OpenCart`: Click **Install**

*Note 1*: The new uploaded plugin will overwrite any previous version.

*Note 2*: By removing the Plugin from the `Extension Installer` admin page, You are removing the configurations of the plugin.

#### Install using FTP method

1. Download the latest version (`paytabs.ocmod.zip`)
2. Upload the folder to `/opencart/system/storage/marketplace/`
3. Go to `"OpenCart admin panel" >> Extensions >> Installer`
4. On the plugin row `PayTabs - OpenCart`: Click **Install**

- - -

### OpenCart 3.x / OpenCart 2.3

Kindly follow the instructions in paytabs-opencart 3.x branch:

https://github.com/paytabscom/paytabs-opencart/tree/opencart-3.x#installation

---

## Activating the Plugin

1. Go to `"OpenCart admin panel" >> Extensions >> Extensions`
2. Select `Payments` option from `Choose the extension type` section
3. Look for the preferred payment method from the available list of PayTabs payment methods *(`PayTabs - CreditCard` for example)*
4. Click the *Green plus* button next to the plugin and wait until the installation completes

---

## Configure the Plugin

1. Go to `"OpenCart admin panel" >> Extensions >> Extensions`
2. Select `Payments` option from `Choose the extension type` section
3. Look for the preferred payment method from the available list of PayTabs payment methods *(`PayTabs - CreditCard` for example)*
4. The edit button *(The blue button)* should be enabled for activated plugins, Click it
5. Select `Enable` for `Status` field
6. Enter the primary credentials:
   - **Profile ID**: Enter the Profile ID of your PayTabs account
   - **Server Key**: `Merchant’s Dashboard >> Developers >> Key management >> Server Key`
7. Configure other options as your need
8. Click the `Save` button *(The blue button on top-right of the page)* button

---

## Log Access

### PayTabs custome log

1. Access `debug_paytabs.log` file found at: `/system/storage/logs/debug_paytabs.log`

### OpenCart error log

1. Navigate to: `"OpenCart admin panel" >> System >> Maintenance >> Error Logs`

---

Done
