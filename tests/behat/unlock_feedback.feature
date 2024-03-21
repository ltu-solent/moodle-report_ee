@report @report_ee @sol @javascript
Feature: Unlock feedback
  As an admin (e.g. Registry)
  In order to allow External Examiners to change locked feedback
  I need to be able unlock locked feedback

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
    And the following SITS assignment exists:
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
    And the following "report_ee > eefeedback" exists:
    | course          | Module1                              |
    | activity        | ABC101_A_SEM1_2023/24_ABC10101_001_0 |
    | sample          | 0                                    |
    | level           | 1                                    |
    | national        | 2                                    |
    | comments        | Really jolly good.                   |
    | locked          | 1                                    |
    | modifiedby      | ee                                   |

  Scenario: EE cannot edit locked feedback
    Given I am on the "Module1" "Course" page logged in as "ee"
    And I navigate to "Reports > External examiner feedback" in current page administration
    And I expand all fieldsets
    Then I should see "Not set" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_sample_select" "css_element"
    And I should see "Yes" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_level_select" "css_element"
    And I should see "No" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_national_select" "css_element"
    And the "comments" "field" should be disabled
    And I should see "Really jolly good." in the "comments" "field"
    And the "locked" "field" should be disabled

  # Registry can unlock this page, and then EE can update it.
  Scenario: Registry wishes to unlock the report
    Given I am on the "Module1" "Course" page logged in as "elreg"
    When I navigate to "Reports > External examiner feedback" in current page administration
    And I expand all fieldsets
    Then I should see "Not set" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_sample_select" "css_element"
    And I should see "Yes" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_level_select" "css_element"
    And I should see "No" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_national_select" "css_element"
    And I click on "locked" "checkbox"
    When I press "Save changes"
    Then I should see "Changes saved, redirecting to course page"
    When I navigate to "Reports > External examiner feedback" in current page administration
    And I expand all fieldsets
    Then the "locked" "field" should be disabled
    And the field "Have you seen samples of completed work for this assessment?" in the "Report 1 (25%)" "fieldset" matches value "Not set"
    And the field "Were the standards set for the assessment appropriate for their level?" in the "Report 1 (25%)" "fieldset" matches value "Yes"
    And the field "Were the standards of student performance comparable with similar programmes or subjects in other UK institutions with which you are familiar?" in the "Report 1 (25%)" "fieldset" matches value "No"
    And I should see "Really jolly good." in the "comments" "field"
    And the "comments" "field" should be enabled
    And "Save changes" "button" should exist
    When I am on the "Module1" "Course" page logged in as "ee"
    And I navigate to "Reports > External examiner feedback" in current page administration
    And I expand all fieldsets
    And the field "Have you seen samples of completed work for this assessment?" in the "Report 1 (25%)" "fieldset" matches value "Not set"
    And the field "Were the standards set for the assessment appropriate for their level?" in the "Report 1 (25%)" "fieldset" matches value "Yes"
    And the field "Were the standards of student performance comparable with similar programmes or subjects in other UK institutions with which you are familiar?" in the "Report 1 (25%)" "fieldset" matches value "No"
    And I should see "Really jolly good." in the "comments" "field"
    And I set the field "Report 1 (25%) > Have you seen samples of completed work for this assessment?" to "Yes"
    Then the "locked" "field" should be enabled
    And I click on "locked" "checkbox"
    When I press "Save changes"
    Then I should see "Changes saved, redirecting to course page"
    When I navigate to "Reports > External examiner feedback" in current page administration
    And I expand all fieldsets
    Then the "locked" "field" should be disabled
    And I should see "Yes" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_sample_select" "css_element"
    And I should see "Yes" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_level_select" "css_element"
    And I should see "No" in the "#id_assignment_ABC101_A_SEM1_202324_ABC10101_001_0 .ee_national_select" "css_element"
