# Import One Stop Shop (IOSS) for eCommerce

As of 1st July 2021 any B2C transaction from outside the EU supplying a customer inside the EU is now liable for the VAT due. They are to charge the customer their local VAT and pay that via an agent registered in the EU. This VAT is paid via the Import One Stop Shop system. You can find more details about this from the EU or Royal Mail here:

https://www.royalmail.com/import-one-stop-shop-ioss

https://ec.europa.eu/taxation_customs/business/vat/ioss_en

# What you need to know

When a customer places an order with a shop outside the EU that is being delivered to an address in the EU the shop must pay the EU VAT for that order. Some points on this:

* The IOSS scheme replaces the old €22 low value item limit - that no longer applies
* This applies to the shop if it is VAT registered in their home country or not - so if you aren't VAT registered in the UK you still need to pay the EU VAT
* The customer is to be charged the VAT that applies to the delivery address. If the shop is registerd in the UK, the billing address is the USA and the delivery address is Spain the shop must pay the VAT for Spain.
* IOSS can only be used on orders €150 or less - above that is a lot more complicated!
* The €150 limit is the net (before VAT) price of the goods - shipping charges are not included in this. The net price is based on the delivery country VAT rate
* If the order falls below the €150 limit even though Shipping isn't used to calculate the limit VAT is charged on the shipping cost
* The order must have a completed CN22 and electronic customs data, these need to include the IOSS number of the company that has paid the VAT (this is not your EORI Number)
* If your company is registered outside the EU you must appoint a company to pay the VAT in the EU for you, it is this companies IOSS number your put on the order
* When shipping the order you do not need to include any documentation / invoices from the company paying the VAT ... just your own invoice is fine with the customs data
* If using an online marketplace (ie eBay, Amazon, Etsy, Not On the High Street etc) all the VAT payments and handling is done through them.

# Taxamo Assure

Taxamo Assure is a new service from Vertex, it allows you to Pay As You Go IOSS VAT payments. You integrate the solution into your own shopping cart, when the customer places an order you pay Taxamo the VAT and then pay £2.00 per delivery. Most other companies charge fixed monthly rates + the VAT.

## PHP Integration

They have released an API to integrate into your shopping cart software but only have limited plugins for eCommerce stores. I have integrated their system into ZenCart but am releasing my test code here for others who are looking into it.

You can find out more about the Taxamo solution and API documentation here: https://www.taxamo.com/taxamo-assure-rmg
