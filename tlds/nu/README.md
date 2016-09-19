Creating necessary database fields
==================================


If you want to use the default admin/tech contact you need to create a database field for the company number:

Table: tblconfiguration

Setting: RegistrarAdminOrganizationNumber

Value: YOURCOMPANYNUMBER

This is needed because there is no way to enter this number in the Domain-Settings Tab. 

If you have the field General Settings -> Domains -> "Tick this box to use clients details for the Billing/Admin/Tech contacts" checked, this field is not needed, and the company number from the order parameters is taken. 


