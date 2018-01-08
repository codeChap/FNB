
You might be more interested in developing for https://root.co.za/

Fnb
===

First National Bank Transaction Package.

## Disclaimer:

Use this library at your own risk, I take no responsibility what so ever for the use of it!

## What does this do?

This script logs into your FNB bank account and pulls all your transactions and puts in a pretty php array.

## So why an automated login?

Because pulling a full history of my accounts daily allows for a more creative approach to banking and invoicing.

## FNB, please build an API

First national bank was announced the most innovative bank in the world. The next step in the banking evolution would be to have APIs built into our banking systems.

## How long will this script work for?

Probably not very long, its based on the structure of the HTML pages so if FNB change there website to much - this script will fail miserably. So I would not go building complex systems around it. Thats what APIs are for.

## Is this legal?

At the time of writing, I have not found anything in FNB's terms and conditions regarding automated login. I will however remove this repository if requested to do so.

## Usage

The very, VERY first thing you do is login to FNB as per usual and create a second READ ONLY user for use with this script. The reasons for this are obvious.

Then use [composer](http://getcomposer.org) to install it or simply include the file somewhere:

```
    require("fnb/src/Fnb/Fnb.php");

    $fnb = new Fnb\Fnb(
    array(
        'username' => 'readOnlyUser',
        'password' => 'readOnlyPassword',
        'verbose' => false,
        'write' => false
        )
    );

    $fnb->pull();

    print "<pre>"; print_r($r); print "</pre>";

```

## Questions

Ask me on twitter if you have any questions: [@codeChap](http://twitter.com/codechap)
