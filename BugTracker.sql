-- You can't create databases on Solace, only tables
-- DROP DATABASE tracker;
-- CREATE DATABASE tracker;
-- USE tracker;

-- Change 'databasename' to your RIT userid before running
USE databasename;

DROP TABLE IF EXISTS project ;
CREATE TABLE project
(
 Id INT NOT NULL AUTO_INCREMENT,
 Project CHAR(50) NOT NULL,
 PRIMARY KEY(Id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS role;
CREATE TABLE role
(
 Id INT NOT NULL AUTO_INCREMENT,
 Role VARCHAR(50) NOT NULL,
 PRIMARY KEY(Id)
) ENGINE=InnoDB;

INSERT INTO role (Role) values ('Admin'),('Manager'),('User');
 
CREATE TABLE user_details
(
 Id INT NOT NULL AUTO_INCREMENT,
 Username CHAR(50) NOT NULL,
 RoleID INT NOT NULL,
 ProjectId INT NULL, -- manager and admin are null
 Password VARCHAR(100), -- must be hashed
 Name VARCHAR(250) NOT NULL,
 PRIMARY KEY(Id),
 KEY `fk_role` (`RoleId`),
 KEY `fk_project_assigned` (`ProjectId`),
 CONSTRAINT `fk_role` FOREIGN KEY (`RoleId`) REFERENCES `role` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
 CONSTRAINT `fk_project_assigned` FOREIGN KEY (`ProjectId`) REFERENCES `project` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB;
 
CREATE TABLE bug_status
(
 Id INT NOT NULL AUTO_INCREMENT,
 Status CHAR(50) NOT NULL,
 PRIMARY KEY(Id)
);

INSERT INTO bug_status (Status) VALUES ('Unassigned'), ('Assigned'), ('Closed');

CREATE TABLE priority
(
 Id INT NOT NULL AUTO_INCREMENT,
 Priority VARCHAR(10) NOT NULL,
 PRIMARY KEY(Id)
);

INSERT INTO priority (Priority) VALUES ('Low'), ('Medium'), ('High'),('Urgent');
 
CREATE TABLE `bugs` (
 `id` INT NOT NULL AUTO_INCREMENT,
 `projectId` INT NOT NULL,
 `ownerId` INT NOT NULL,
 `assignedToId` INT NULL,
 `statusId` INT NOT NULL,
 `priorityId` INT NOT NULL,
 `summary` VARCHAR(250) NOT NULL,
 `description` VARCHAR(2500) NOT NULL,
 `fixDescription` VARCHAR(2500) DEFAULT NULL,
 `dateRaised` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 `targetDate` timestamp NULL DEFAULT NULL,
 `dateClosed` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`id`),
 KEY `fk_project` (`projectId`),
 KEY `fk_owner` (`ownerId`),
 KEY `fk_assigned` (`assignedToId`),
 KEY `fk_status` (`statusId`),
 KEY `fk_priority` (`priorityId`),
 CONSTRAINT `fk_project` FOREIGN KEY (`projectId`) REFERENCES `project` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
 CONSTRAINT `fk_owner` FOREIGN KEY (`ownerId`) REFERENCES `user_details` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
 CONSTRAINT `fk_assigned` FOREIGN KEY (`assignedToId`) REFERENCES `user_details` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
 CONSTRAINT `fk_status` FOREIGN KEY (`statusId`) REFERENCES `bug_status` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
 CONSTRAINT `fk_priority` FOREIGN KEY (`priorityId`) REFERENCES `priority` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1