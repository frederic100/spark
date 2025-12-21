# language: en
Feature: create the tenant of the application

    Scenario: create the tenant of the application
    Given the application is installed
    And I am the tenant administrator
    And the tenant is not created
    When I initiate the tenant creation
    Then the tenant is created