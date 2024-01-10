# Clickpay-opencart

The official **OpenCart** Plugin for Clickpay (PT-2).

Supports OpenCart **3.x** & **2.3**.
- - -

## Installation

### Install using OpenCart Admin panel

1. Download the latest release of the plugin 
2. Go to `"OpenCart admin panel" >> Extensions >> Installer`
3. Click `Upload`
4. Select the downloaded zip file (`clickpay-opencart.zip`)
5. Wait until the upload *Progress* success
6. Apply the modifications [Details](#applying-the-modifications)


*Notes*:
- The new uploaded plugin will overwrite any previous version.
- By removing the Plugin from the `Extension Installer` admin page, You are removing the configurations of the plugin.


### Install using FTP method

1. Upload the content of this repo to the root folder of your OpenCart's website
2. Apply the modifications [Details](#applying-the-modifications)


*Note: In case a previous version already installed, Replace all previous files when asking.*

- - -


## Applying the Modifications

Applying the modifications is essential to enable the actions on the Sale Orders (such as **`Refund`**).

Depends on the installation type:

### Installed using OpenCart Admin panel

1. Navigate to `OpenCart Admin panel >> Extensions >> Modifications`.
2. Click on the **`Refresh`** button *(top-right next to `Clear` & `Delete` buttons)*, you should see a successful message saying: "`Success: You have modified modifications!`".

### Installed using FTP method

1. Zip the `install.xml` file and name it `install.ocmod.zip`.
2. Navigate to `OpenCart Admin panel >> Extensions >> Installer`.
3. Click Upload button and select the `install.ocmod.zip` file.
4. Navigate to `OpenCart Admin panel >> Extensions >> Modifications`.
5. Click on the **`Refresh`** button *(top-right next to `Clear` & `Delete` buttons)*, you should see a successful message saying: "`Success: You have modified modifications!`".

## Activating the Plugin

1. Go to `"OpenCart admin panel" >> Extensions >> Extensions`
2. Select `Payments` option from `Choose the extension type` section
3. Look for the preferred payment method from the available list of Clickpay payment methods *(`Clickpay - CreditCard` for example)*
4. Click the *Green plus* button next to the plugin and wait until the installation completes

- - -

## Configure the Plugin

1. Go to `"OpenCart admin panel" >> Extensions >> Extensions`
2. Select `Payments` option from `Choose the extension type` section
3. Look for the preferred payment method from the available list of Clickpay payment methods *(`Clickpay - CreditCard` for example)*
4. The edit button *(The blue button)* should be enabled for activated plugins, Click it
5. Select `Enable` for `Status` field
6. Enter the primary credentials:
   - **Profile ID**: Enter the Profile ID of your Clickpay account
   - **Server Key**: `Merchant’s Dashboard >> Developers >> Key management >> Server Key`
7. Configure other options as your need
8. Click the `Save` button *(The blue button on top-right of the page)* button

- - -
## Enable Refund Functionality

You should apply the modifications in order to have the Refund functionality [More details](#applying-the-modifications).



## Log Access

### Clickpay custome log

1. Access `debug_clickpay.log` file found at: `/system/storage/logs/debug_clickpay.log`

### OpenCart error log

1. Navigate to: `"OpenCart admin panel" >> System >> Maintenance >> Error Logs`

- - -

Done
