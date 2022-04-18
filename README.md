# Custom Form & Narrative Reporting System

This set of php scripts automatically builds web forms from simple spreadsheet TSV files, saves the questions, responses and corresponding narratives as JSON data to a new table in the OpenEMR MySQL database and either links to an existing patient record or creates a new patient record in the OpenEMR `patient_data` table. The results can be viewed by clicking a button within a Layout Based form from a Patient Encounter in OpenEMR. The web forms support multiple sites and languages. An additional feature to auto-generate PDF reports incorporating the corresponding narrative is also supported.

#### URLs

In order for the forms to support multiple sites/databases, multiple languages and multiple clinic names, configuration is passed by URL parameters as outlined below.

To avoid having to communicate long complicated URLs to patients, it is recommended that a free shortening service like [bitly](https://bitly.com/) is used to create a set of short URLs that are then redirected to the corresponding OpenEMR URL.

eg the short URL: `https://bit.ly/tcfn-es`

can be created and used to redirect to:

`http://server/<context>/cforms/?type=intake&site=default&es=1&header=Texas Center For Neuroscience`

A set of these URLs can be generated for each of the different clinics/sites/languages and then copy-pasted into emails etc.

##### Examples

- `http://server/<context>/cforms?type=intake&site=default`
  Web form generated with `intake.tsv` from `default` site in English
- `http://server/<context>/cforms?type=followup&site=myclinic`
  Web form generated with `followup.tsv` from `myclinic` site in English
- `http://server/<context>/cforms?type=intake&site=default&header=Remi Nadar MD`
  Web form generated with `intake.tsv` from `default` site in English with the header "Remi Nadar MD" 
- `http://server/<context>/cforms?type=intake&site=default&es=1`
  Web form generated with `intake.tsv` from `default` site in Spanish
- `http://server/<context>/cforms/response.php?type=intake&site=default&pid=3`
  The most recent Response for the form generated with `intake.tsv` from `default` site for Patient ID `3`
- `http://server/<context>/cforms/response.php?site=default&id=22`
  The Response from `default` site for Response ID 22
- `http://server/<context>/cforms/responses.php?site=default`
  List of all responses from `default` site
- `http://server/<context>/cforms/report.php?site=default&id=22`
  Report from `default` site for Response ID 22

### Updating Questions/Narratives from a Spreadsheet

Make a copy of this spreadsheet into your own Google Drive (sign-in to Gmail before following this link then choose File > Create copy)

https://docs.google.com/spreadsheets/d/1PcBiNGUUY8JXTuHadZg6FFdGwL2yOhlVuS6xLnyjyd8/edit?usp=sharing

Select the sheet from the bottom tabs, eg 'intake' then export/download the sheet as a TSV file and copy to the `./forms/<form type>.tsv` path, eg:

```
/var/www/html/openemr/cforms/forms/intake.tsv
OR
/var/www/html/openemr/cforms/forms/followup.tsv
OR
/var/www/html/openemr/cforms/forms/myform.tsv
```

- *Column A:* Identifier - each question needs a unique id. The value/sequence of the identifiers does not matter - the webform will use the order of the rows in the tsv regardless. Special identifiers `TITLE` and `TEXT` are used to display the corresponding values rather than questions.
- *Column B:* Input type rendered on web form (`short_text`, `long_text` or `date`)
- *Column C:* Question or Group Label or Title/Text displayed on web form
- *Column D:* Question in Spanish
- *Column E:* Narrative used for template

### Initial Installation & Set up

Copy the `intake` directory to the top level OpenEMR directory, eg

```
$ cd /var/www/html/openemr/cforms
$ tree . 
.
├── README.md
├── controller.php
├── css
│   ├── bootstrap.css
│   └── bootstrap.css.map
├── forms
│   ├── followup.tsv
│   └── intake.tsv
├── index.php
├── js
│   ├── bootstrap.bundle.js
│   ├── bootstrap.bundle.js.map
│   └── jquery-3.2.1.slim.min.js
├── report.php
├── reports
├── response.php
├── responses.php
└── sql
    └── create_table.sql
```

#### Database Config

Create the intake table in the MySQL databases

###### NB: Every time a new OpenEMR site/database is created this must be run below to create the data table.

```sql
USE <database name>;

CREATE TABLE IF NOT EXISTS cforms (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    form_type text,
    created_at datetime default now(),
    pid BIGINT,
    fname text,
    lname text,
    dob date,
    data JSON
)  ENGINE=INNODB;
```

#### Patching OpenEMR

- open the file `interface/forms/LBF/new.php`
- locate the string `echo FeeSheetHtml::genProviderSelect('form_provider_id`
- Below that line add the following code

```php
echo "<script>const irSettings=' width='+window.outerWidth/2+', left='+window.outerWidth/2+',height='+window.outerHeight+',location=0, resizable, scrollbars, toolbar=0, menubar=0';</script>";
echo '<div class="float-right">';
echo '<button type="button" onclick="window.open(\'/cforms/response.php?type=intake&pid='.$pid.'&site='.$_SESSION['site_id'].'\',\'iv\',irSettings)">Intake Response</button>';
echo '<button type="button" onclick="window.open(\'/cforms/response.php?type=followup&pid='.$pid.'&site='.$_SESSION['site_id'].'\',\'iv\',irSettings)">Follow Up Response</button>';
echo '</div>';
```

#### Updating the Narrative Report

The narrative report is a simple PHP file that pulls all the data from MySQL corresponding to the particular record id and renders a HTML page. The data can be included in the HTML page as follows:

```php
To print template values:
Lookup the question id from the spreadsheet, eg A01

<?php echo $data->{"A01"}->{"g"}?>  This prints the group, eg Demographics
<?php echo $data->{"A01"}->{"q"}?>  This prints the question, eg Last Name
<?php echo $data->{"A01"}->{"r"}?>  This prints the response, eg Smith
<?php echo $data->{"A01"}->{"n"}?>  This prints the narative, eg The patient's last name is
```

See *report.php* for examples

### Overview

- `index.php` reads in the tsv file and builds the web form HTML with Bootstrap CSS.
- Every input in the form also has a hidden field for the corresponding Question in English, Group (eg *Demographics*) and Narrative (eg *The patient's last name is*). This captures all the form context data alongside every response so if the form changes at a later date the original questions/narratives are still saved.
- When the form is submit a JSON POST is sent to the controller `controller.php` .
- Using credentials from corresponding OpenEMR site, the controller firstly looks-up the patient by first name, last name and DOB in the OpenEMR `patient_data` table. If the patient does not already exist a new patient record is created.
- The controller then saves all the data into the `cforms` table (configured above). The JSON is saved as one big record but patient_id, first/last name and DOB are separated out for easy querying.
- Optionally, the controller then calls chrome in headless mode to fetch `report.php` and print it to a PDF.
- `responses.php` lists all responses from the database and allows responses to be deleted from the `intake` table only.
- `response.php` is launched from within OpenEMR (configured above) or from `responses.php` and displays the questions, answers and narratives for the corresponding Patient ID. If there are multiple responses for the one patient, OpenEMR displays the most recent response. Previous responses can be viewed from `responses.php` .
- `report.php` displays a templated narrative response for the corresponding response ID.