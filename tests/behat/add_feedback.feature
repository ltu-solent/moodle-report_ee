@report @report_ee @sol @javascript
Feature: External examiners give feedback
  As an External examiner
  In order to give my feedback
  I need to be able to fill-in feedback info

  Background:
    Given the following "users" exist:
    | username | email             | firstname | lastname |
    | ml       | ml@example.com    | Module    | Leader   |
    | ee       | ee@example.com    | External  | Examiner |
    | student  | s@example.com     | Student   | One      |
    | tutor    | tutor@example.com | Tutor     | One      |
    | elreg    | elreg@example.com | El        | Registry |
    And the following "courses" exist:
    | fullname | shortname | idnumber | startdate       | enddate          |
    | Module1  | Module1   | Module1  | ## 2023-09-25 ##| ## 2024-01-15 ## |
    And the following "roles" exist:
    | shortname        | name              | archetype      |
    | moduleleader     | Module leader     | editingteacher |
    | externalexaminer | External examiner | teacher        |
    | registry         | Registry          | manager        |
    And the following "course enrolments" exist:
    | user     | course  | role             |
    | ml       | Module1 | moduleleader     |
    | ee       | Module1 | externalexaminer |
    | tutor    | Module1 | editingteacher   |
    | student  | Module1 | student          |
    And I log in as "admin"
    And the solent gradescales are setup
    And the following config values are set as admin:
    | config                    | value           | plugin                    |
    | blindmarking              | 1               | assign                    |
    | markingworkflow           | 1               | assign                    |
    | default                   | 1               | assignfeedback_misconduct |
    | default                   | 1               | assignfeedback_doublemark |
    | cutoffinterval            | 1               | local_quercus_tasks       |
    | cutoffintervalsecondplus  | 1               | local_quercus_tasks       |
    | gradingdueinterval        | 2               | local_quercus_tasks       |
    | studentregemail           | reg@example.com | report_ee                 |
    | qualityemail              | qa@example.com  | report_ee                 |
    | moduleleadershortname     | ml              | report_ee                 |
    | externalexaminershortname | ee              | report_ee                 |
    And the following "role capabilities" exist:
    | role             | report/ee:admin | report/ee:edit | report/ee:view |
    | externalexaminer | prohibit        | allow          | allow          |
    | moduleleader     | prohibit        | prohibit       | allow          |
    | registry         | allow           | allow          | allow          |
    And the following "role assigns" exist:
    | user  | role     | contextlevel | reference |
    | elreg | registry | System       |           |

  Scenario: SITS assignments are present
    Given the following SITS assignment exists:
    | sitsref         | ABC101_A_SEM1_2023/24_ABC10101_001_0 |
    | course          | Module1                              |
    | title           | Report 1 (25%)                       |
    | weighting       | 25                                   |
    | duedate         | ## 5 May 2023 16:00:00 ##            |
    | assessmentcode  | ABC10101                             |
    | assessmentname  | Report 1                             |
    | sequence        | 001                                  |
    | availablefrom   | 0                                    |
    | reattempt       | 0                                    |
    | grademarkexempt | 0                                    |
    And the following SITS assignment exists:
    | sitsref         | ABC101_A_SEM1_2023/24_ABC10101_002_0 |
    | course          | Module1                              |
    | title           | Report 2 (25%)                       |
    | weighting       | 25                                   |
    | duedate         | ## 2 June 2023 16:00:00 ##           |
    | assessmentcode  | ABC10101                             |
    | assessmentname  | Report 2                             |
    | sequence        | 002                                  |
    | availablefrom   | ## 2 June 2023 09:00:00 ##           |
    | reattempt       | 0                                    |
    | grademarkexempt | 1                                    |
    And the following SITS assignment exists:
    | sitsref         | ABC101_A_SEM1_2023/24_ABC10101_002_1 |
    | course          | Module1                              |
    | title           | Report 2b (25%)                      |
    | weighting       | 25                                   |
    | duedate         | ## 2 June 2023 16:00:00 ##           |
    | assessmentcode  | ABC10101                             |
    | assessmentname  | Report 2                             |
    | sequence        | 002                                  |
    | availablefrom   | ## 2 June 2023 09:00:00 ##           |
    | reattempt       | 1                                    |
    | grademarkexempt | 1                                    |
    And I am on the "Module1" "Course" page logged in as "ee"
    And I navigate to "Reports > External examiner feedback" in current page administration
    And I expand all fieldsets
    Then I should not see "There are no assessments in the module."
    And I should see "Report 1 (25%)" in the "#region-main" "css_element"
    And I should see "Report 2 (25%)" in the "#region-main" "css_element"
    # Reattempts are not shown.
    And I should not see "Report 2b (25%)" in the "#region-main" "css_element"
    And I should see "I have completed this form and wish to submit it"
    And the "locked" "field" should be enabled
    And I press "Save changes"
    And I navigate to "Reports > External examiner feedback" in current page administration
    And I expand all fieldsets
    And I should see "Report 1 (25%)"
    And I should see "Report 2 (25%)"
    And I should see "I have completed this form and wish to submit it"
    And the "locked" "field" should be enabled
    And I set the field "Report 1 (25%) > Have you seen samples of completed work for this assessment?" to "Yes"
    And I set the field "Report 1 (25%) > Were the standards set for the assessment appropriate for their level?" to "No"
    And I set the field "Report 1 (25%) > Were the standards of student performance comparable with similar programmes or subjects in other UK institutions with which you are familiar?" to "Yes"
    And I press "Save changes"
    When I navigate to "Reports > External examiner feedback" in current page administration
    And I expand all fieldsets
    Then the field "Have you seen samples of completed work for this assessment?" in the "Report 1 (25%)" "fieldset" matches value "Yes"
    And the field "Were the standards set for the assessment appropriate for their level?" in the "Report 1 (25%)" "fieldset" matches value "No"
    And the field "Were the standards of student performance comparable with similar programmes or subjects in other UK institutions with which you are familiar?" in the "Report 1 (25%)" "fieldset" matches value "Yes"
    And I click on "locked" "checkbox"
    When I press "Save changes"
    Then I should see "You must enter a comment"
    And I should not see "You must select either Yes or No." in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_sample_select" "css_element"
    And I should not see "You must select either Yes or No." in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_level_select" "css_element"
    And I should not see "You must select either Yes or No." in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_national_select" "css_element"
    And I should see "You must select either Yes or No." in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_002_0 .ee_sample_select" "css_element"
    And I should see "You must select either Yes or No." in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_002_0 .ee_level_select" "css_element"
    And I should see "You must select either Yes or No." in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_002_0 .ee_national_select" "css_element"
    And I set the field "Report 2 (25%) > Have you seen samples of completed work for this assessment?" to "Yes"
    And I set the field "Report 2 (25%) > Were the standards set for the assessment appropriate for their level?" to "No"
    And I set the field "Report 2 (25%) > Were the standards of student performance comparable with similar programmes or subjects in other UK institutions with which you are familiar?" to "Yes"
    And I set the field "comments" to "This is my feedback on Module1"
    When I press "Save changes"
    Then I should see "Changes saved, redirecting to course page"
    When I navigate to "Reports > External examiner feedback" in current page administration
    And I expand all fieldsets
    Then I should see "Yes" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_sample_select" "css_element"
    And I should see "No" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_level_select" "css_element"
    And I should see "Yes" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_national_select" "css_element"
    And I should see "Yes" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_002_0 .ee_sample_select" "css_element"
    And I should see "No" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_002_0 .ee_level_select" "css_element"
    And I should see "Yes" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_002_0 .ee_national_select" "css_element"
    And the "comments" "field" should be disabled
    And I should see "This is my feedback on Module1" in the "comments" "field"
    And the "locked" "field" should be disabled

  Scenario: No summative assignments
    Given I am on the "Module1" "Course" page logged in as "ee"
    And I navigate to "Reports > External examiner feedback" in current page administration
    Then I should see "There are no assessments in this module."
    When I am on the "Module1" "Course" page logged in as "ml"
    And I navigate to "Reports > External examiner feedback" in current page administration
    Then I should see "There are no assessments in this module."
    When I am on the "Module1" "Course" page logged in as "tutor"
    And I navigate to "Reports > External examiner feedback" in current page administration
    Then I should see "There are no assessments in this module."

  Scenario: Quercus assignments are present
    Given the following Quercus assignment exists:
    | course                | Module1                    |
    | weighting             | .25                        |
    | assessmentCode        | Report1_2022               |
    | assessmentDescription | Report 1                   |
    | dueDate               | ## 5 May 2023 16:00:00 ##  |
    | academicYear          | 2022                       |
    And the following Quercus assignment exists:
    | course                | Module1                    |
    | weighting             | .25                        |
    | assessmentCode        | Report2_2022               |
    | assessmentDescription | Report 2                   |
    | dueDate               | ## 6 June 2023 16:00:00 ## |
    | academicYear          | 2022                       |
    And the following Quercus assignment exists:
    | course                | Module1                    |
    | weighting             | .25                        |
    | assessmentCode        | Report2b_2022              |
    | assessmentDescription | Report 2b                  |
    | dueDate               | ## 6 June 2023 16:00:00 ## |
    | academicYear          | 2022                       |
    | sittingDescription    | SECOND_SITTING             |
    And I am on the "Module1" "Course" page logged in as "ee"
    And I navigate to "Reports > External examiner feedback" in current page administration
    And I expand all fieldsets
    Then I should not see "There are no assessments in the module."
    And I should see "Report 1 (25%)" in the "#region-main" "css_element"
    And I should see "Report 2 (25%)" in the "#region-main" "css_element"
    And I should not see "Report 2b (25%)" in the "#region-main" "css_element"
    And I should see "I have completed this form and wish to submit it"
    And the "locked" "field" should be enabled
    And I press "Save changes"
    And I navigate to "Reports > External examiner feedback" in current page administration
    And I expand all fieldsets
    And I should see "Report 1 (25%)"
    And I should see "Report 2 (25%)"
    And I should see "I have completed this form and wish to submit it"
    And the "locked" "field" should be enabled
    And I set the field "Report 1 (25%) > Have you seen samples of completed work for this assessment?" to "Yes"
    And I set the field "Report 1 (25%) > Were the standards set for the assessment appropriate for their level?" to "No"
    And I set the field "Report 1 (25%) > Were the standards of student performance comparable with similar programmes or subjects in other UK institutions with which you are familiar?" to "Yes"
    And I press "Save changes"
    When I navigate to "Reports > External examiner feedback" in current page administration
    And I expand all fieldsets
    Then the field "Have you seen samples of completed work for this assessment?" in the "Report 1 (25%)" "fieldset" matches value "Yes"
    And the field "Were the standards set for the assessment appropriate for their level?" in the "Report 1 (25%)" "fieldset" matches value "No"
    And the field "Were the standards of student performance comparable with similar programmes or subjects in other UK institutions with which you are familiar?" in the "Report 1 (25%)" "fieldset" matches value "Yes"
    And I click on "locked" "checkbox"
    When I press "Save changes"
    Then I should see "You must enter a comment"
    And I should not see "You must select either Yes or No." in the "#id_assignment_2022_Report1_2022 .ee_sample_select" "css_element"
    And I should not see "You must select either Yes or No." in the "#id_assignment_2022_Report1_2022 .ee_level_select" "css_element"
    And I should not see "You must select either Yes or No." in the "#id_assignment_2022_Report1_2022 .ee_national_select" "css_element"
    And I should see "You must select either Yes or No." in the "#id_assignment_2022_Report2_2022 .ee_sample_select" "css_element"
    And I should see "You must select either Yes or No." in the "#id_assignment_2022_Report2_2022 .ee_level_select" "css_element"
    And I should see "You must select either Yes or No." in the "#id_assignment_2022_Report2_2022 .ee_national_select" "css_element"
    And I set the field "Report 2 (25%) > Have you seen samples of completed work for this assessment?" to "Yes"
    And I set the field "Report 2 (25%) > Were the standards set for the assessment appropriate for their level?" to "No"
    And I set the field "Report 2 (25%) > Were the standards of student performance comparable with similar programmes or subjects in other UK institutions with which you are familiar?" to "Yes"
    And I set the field "comments" to "This is my feedback on Module1"
    When I press "Save changes"
    Then I should see "Changes saved, redirecting to course page"
    When I navigate to "Reports > External examiner feedback" in current page administration
    And I expand all fieldsets
    Then I should see "Yes" in the "#id_assignment_2022_Report1_2022 .ee_sample_select" "css_element"
    And I should see "No" in the "#id_assignment_2022_Report1_2022 .ee_level_select" "css_element"
    And I should see "Yes" in the "#id_assignment_2022_Report1_2022 .ee_national_select" "css_element"
    And I should see "Yes" in the "#id_assignment_2022_Report2_2022 .ee_sample_select" "css_element"
    And I should see "No" in the "#id_assignment_2022_Report2_2022 .ee_level_select" "css_element"
    And I should see "Yes" in the "#id_assignment_2022_Report2_2022 .ee_national_select" "css_element"
    And the "comments" "field" should be disabled
    And I should see "This is my feedback on Module1" in the "comments" "field"
    And the "locked" "field" should be disabled
