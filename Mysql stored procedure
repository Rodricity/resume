CREATE PROCEDURE `datahub_cursos`.`informe_cierre`(IN `_cursoID` BIGINT)
BEGIN
	
	-- Variables para iterar sobre actividades
	DECLARE actividad_tipo VARCHAR(32) DEFAULT "";
    DECLARE actividad_id BIGINT DEFAULT NULL;
    DECLARE actividad_nombre VARCHAR(128) DEFAULT "";
    DECLARE actividad_calificada TINYINT DEFAULT 0;
    
    -- Variables para iterar sobre los aprendices.
    DECLARE aprendizID BIGINT DEFAULT 0;
    DECLARE username VARCHAR(64) DEFAULT NULL;
    DECLARE nombre VARCHAR(128) DEFAULT NULL;
    DECLARE apellido VARCHAR(128) DEFAULT NULL;
    DECLARE seccion VARCHAR(128) DEFAULT NULL;
    DECLARE sede VARCHAR(64) DEFAULT "";
    
    -- Variables para almacenar datos del tutor del curso.
    DECLARE tutor_username VARCHAR(64) DEFAULT NULL;
    DECLARE tutor_nombre VARCHAR(128) DEFAULT NULL;
    DECLARE tutor_apellido VARCHAR(128) DEFAULT NULL;
    
    -- Variable para el total de contenido del curso.
    DECLARE progreso_estudiante INT DEFAULT 0;
    DECLARE total_contenido INT DEFAULT 0;
    
	-- Tabla para almacenar las actividades encontradas para el curso en cuestion.
	DROP TEMPORARY TABLE IF EXISTS tmp_actividades_curso;
    CREATE TEMPORARY TABLE tmp_actividades_curso(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, TipoActividad VARCHAR(32) NOT NULL, ActivityId BIGINT NOT NULL, NombreActividad VARCHAR(64) NOT NULL, ActividadCalificada TINYINT NOT NULL DEFAULT 0);
     
    -- Luego buscamos por cada actividad. Buzones, Cuestionarios y Debates
    INSERT INTO tmp_actividades_curso SELECT NULL,"Dropbox",ASSU.DropboxId,TRIM(SUBSTRING(ASSU.Name,1,64)),0 FROM AssignmentSummary ASSU WHERE OrgUnitId = _cursoID AND IsHidden=0 AND IsDeleted=0;
    INSERT INTO tmp_actividades_curso SELECT NULL,"Quizzes",QO.QuizId,TRIM(SUBSTRING(QO.QuizName,1,64)),0 FROM QuizObjects QO WHERE OrgUnitId = _cursoID AND IsActive=1;
    INSERT INTO tmp_actividades_curso SELECT NULL,"Discussion",DF.ForumId,TRIM(SUBSTRING(DF.Name,1,64)),0 FROM DiscussionForums DF WHERE OrgUnitId = _cursoID AND IsHidden=0 AND IsDeleted=0;

    -- Actualizamos las tareas que son calificadas de las que no lo son.
    UPDATE tmp_actividades_curso T1 INNER JOIN GradeObjects GO ON GO.ToolName = T1.TipoActividad AND GO.AssociatedToolItemId = T1.ActivityId AND GO.IsDeleted=0 SET T1.ActividadCalificada = 1;

	-- Primero necesitamos crear una 'tabla' que refleje las actividades y progreso de cada usuario del curso solicitado.
    DROP TEMPORARY TABLE IF EXISTS tmp_informe_cierre;
    SET @create_table = 'CREATE TEMPORARY TABLE tmp_informe_cierre (';
    set @create_table = CONCAT(@create_table, 'Aprendiz_ID BIGINT NOT NULL,');
    set @create_table = CONCAT(@create_table, 'Aprendiz_Rut VARCHAR(64) NOT NULL,');
    set @create_table = CONCAT(@create_table, 'Aprendiz_Nombre VARCHAR(64) NOT NULL,');
    set @create_table = CONCAT(@create_table, 'Aprendiz_Apellido VARCHAR(64) NOT NULL,');
    -- set @create_table = CONCAT(@create_table, 'Tutor_Rut VARCHAR(64) NULL DEFAULT NULL,');
    -- set @create_table = CONCAT(@create_table, 'Tutor_Nombre VARCHAR(64) NULL DEFAULT NULL,');
    -- set @create_table = CONCAT(@create_table, 'Tutor_Apellido VARCHAR(64) NULL DEFAULT NULL,');
    set @create_table = CONCAT(@create_table, 'Sede VARCHAR(128) NULL DEFAULT NULL,');
    set @create_table = CONCAT(@create_table, 'Seccion VARCHAR(128) NULL DEFAULT NULL,');
    
    -- Insertamos las actividades como columnas. Para ello iteramos sobre las actividades que recuperamos antes.
    BEGIN
		DECLARE fin_cursor INT DEFAULT 0;
		DECLARE cursor_actividades CURSOR FOR SELECT TipoActividad,ActivityId,NombreActividad FROM tmp_actividades_curso act ORDER BY ActividadCalificada,id ASC;
        DECLARE CONTINUE HANDLER FOR NOT FOUND SET fin_cursor = 1;
        
        OPEN cursor_actividades;
        loop_cursor: LOOP
        
			FETCH cursor_actividades INTO actividad_tipo, actividad_id, actividad_nombre;
			IF fin_cursor THEN LEAVE loop_cursor; END IF;
            
            set @create_table = CONCAT(@create_table, '`',actividad_nombre,'` VARCHAR(20) NOT NULL DEFAULT "",');
        
        END LOOP loop_cursor;
        CLOSE cursor_actividades;
    END;
    
    -- Por ultimo se inserta la columna de progreso
    set @create_table = CONCAT(@create_table, '`Progreso de contenido` VARCHAR(20) NULL DEFAULT NULL');
    set @create_table = CONCAT(@create_table, ")");
    
    -- Y Finalmente creamos la estructura dinamica de la tabla.
	PREPARE create_table FROM @create_table;
    EXECUTE create_table;
    DEALLOCATE PREPARE create_table;
    
    -- Recuperamos el tutor del curso. PENDIENTE
    
    -- Total de contenido del curso
    SELECT COUNT(1) INTO total_contenido FROM ContentObjects WHERE OrgUnitId = _cursoID AND IsHidden=0 AND IsDeleted=0 AND ContentObjectType = "Topic";
    
    -- Ahora buscamos los aprendices de este curso para verificar si han realizado las tareas correspondientes.
    BEGIN
		DECLARE fin_cursor INT DEFAULT 0;
		DECLARE cursor_aprendices CURSOR FOR 
			SELECT U.UserId, U.Username, U.FirstName, U.LastName FROM Users U INNER JOIN UserEnrollments UE ON UE.UserId = U.UserId AND UE.OrgUnitId = _cursoID AND RoleName IN ('Aprendiz','AprendizBasico');
		DECLARE CONTINUE HANDLER FOR NOT FOUND SET fin_cursor = 1;
        
        OPEN cursor_aprendices;
        loop_aprendices: LOOP
        
			FETCH cursor_aprendices INTO aprendizID, username, nombre, apellido;
			IF fin_cursor THEN LEAVE loop_aprendices; END IF;
            
            -- Datos de la 'Sede'
            BEGIN
                DECLARE CONTINUE HANDLER FOR NOT FOUND SET sede = "";
                SELECT ORG.Name INTO sede FROM OrganizationalUnits ORG
                INNER JOIN UserEnrollments UE ON UE.OrgUnitId = ORG.OrgUnitId AND UE.UserId = aprendizID
                WHERE Type IN ("Sede","Negocio")
                ORDER BY EnrollmentDate DESC LIMIT 1;
            END;
            
            -- Datos de la 'Seccion'
            BEGIN
               DECLARE CONTINUE HANDLER FOR NOT FOUND SET seccion = "";
					SELECT ou.Name INTO seccion FROM OrganizationalUnitParents oup 
					INNER JOIN OrganizationalUnits ou ON oup.OrgUnitId = ou.OrgUnitId
					INNER JOIN UserEnrollments ue ON oup.OrgUnitId = ue.OrgUnitId AND ue.UserId = aprendizID
					WHERE oup.ParentOrgUnitId = _cursoID AND ou.`Type` = 'Section';
            END;
            
            -- Generamos la instruccion INSERT para este estudiante.
            INSERT INTO tmp_informe_cierre(Aprendiz_ID,Aprendiz_Rut,Aprendiz_Nombre,Aprendiz_Apellido,Sede,Seccion)
				VALUES (aprendizID,username,nombre,apellido,sede,seccion);
                
			-- Y luego recuperamos el progreso del estudiante en este curso.
            SELECT COUNT(1) INTO progreso_estudiante FROM ContentUserCompletion CUC WHERE CUC.UserId = aprendizID AND OrgUnitId = _cursoID
            AND ContentObjectId IN (SELECT ContentObjectId FROM ContentObjects WHERE OrgUnitId = _cursoID AND IsHidden=0 AND IsDeleted=0 AND ContentObjectType = "Topic");
            
            -- Luego por este estudiante, iteramos sobre las actividades y recuperamos su calificacion, si es que existe.
            BEGIN
				DECLARE calificacion_actividad VARCHAR(20) DEFAULT "";
				DECLARE fin_cursor INT DEFAULT 0;
				DECLARE cursor_actividades CURSOR FOR SELECT TipoActividad,ActivityId,NombreActividad,ActividadCalificada FROM tmp_actividades_curso act ORDER BY 1 ASC;
				DECLARE CONTINUE HANDLER FOR NOT FOUND SET fin_cursor = 1;
				
				OPEN cursor_actividades;
				loop_actividades: LOOP
				
					FETCH cursor_actividades INTO actividad_tipo, actividad_id, actividad_nombre, actividad_calificada;
					IF fin_cursor THEN LEAVE loop_actividades; END IF;
					
                    IF actividad_calificada = 1 THEN
						-- La actividad es calificada, deberia haber un objeto GradeResult que tiene su 'nota'
						BEGIN
							DECLARE CONTINUE HANDLER FOR NOT FOUND SET calificacion_actividad = "";
                            
							SELECT COALESCE(REPLACE(GR.PointsNumerator/GR.PointsDenominator * GO.Weight,".",","),"") INTO calificacion_actividad FROM GradeResults GR
							INNER JOIN GradeObjects GO ON GO.GradeObjectId = GR.GradeObjectId AND GO.ToolName = actividad_tipo AND GO.AssociatedToolItemId = actividad_id
							WHERE GR.OrgUnitId = _cursoID AND GR.UserId = aprendizID AND GO.IsDeleted = 0;
                            
                            IF calificacion_actividad = "" THEN
								-- Si es que no logramos obtener la calificacion, verificamos si es que no existe envío de trabajo.
                                IF actividad_tipo = "Dropbox" THEN
									BEGIN
										DECLARE CONTINUE HANDLER FOR NOT FOUND SET calificacion_actividad = "No enviado";
										SELECT "No evaluado" INTO calificacion_actividad FROM AssignmentSubmissions ASSU WHERE ASSU.DropboxId = actividad_id AND ASSU.UserId = aprendizID ORDER BY 1 DESC LIMIT 1;
									END;
									
								ELSEIF actividad_tipo = "Quizzes" THEN
									BEGIN
										DECLARE CONTINUE HANDLER FOR NOT FOUND SET calificacion_actividad = "No enviado";
										SELECT "No evaluado" INTO calificacion_actividad FROM QuizAttempts QA WHERE QA.QuizId = actividad_id AND QA.UserId = aprendizID ORDER BY 1 DESC LIMIT 1;
									END;
									
								ELSEIF actividad_tipo = "Discussion" THEN
									BEGIN
										DECLARE CONTINUE HANDLER FOR NOT FOUND SET calificacion_actividad = "No enviado";
										SELECT "No evaluado" INTO calificacion_actividad FROM DiscussionPosts DP WHERE DP.ForumId = actividad_id AND DP.UserId = aprendizID AND IsDeleted=0 ORDER BY 1 DESC LIMIT 1;
									END;
									
								ELSE
									SET calificacion_actividad = "N/A";
								END IF;
                            END IF;
						END;
					ELSE
						-- Actividad no calificada, solo buscamos si el usuario la realizó o no.
                        IF actividad_tipo = "Dropbox" THEN
							BEGIN
								DECLARE CONTINUE HANDLER FOR NOT FOUND SET calificacion_actividad = "0";
								SELECT "1" INTO calificacion_actividad FROM AssignmentSubmissions ASSU WHERE ASSU.DropboxId = actividad_id AND ASSU.UserId = aprendizID ORDER BY 1 DESC LIMIT 1;
							END;
                            
						ELSEIF actividad_tipo = "Quizzes" THEN
							BEGIN
								DECLARE CONTINUE HANDLER FOR NOT FOUND SET calificacion_actividad = "0";
								SELECT "1" INTO calificacion_actividad FROM QuizAttempts QA WHERE QA.QuizId = actividad_id AND QA.UserId = aprendizID ORDER BY 1 DESC LIMIT 1;
							END;
                            
						ELSEIF actividad_tipo = "Discussion" THEN
							BEGIN
								DECLARE CONTINUE HANDLER FOR NOT FOUND SET calificacion_actividad = "0";
								SELECT "1" INTO calificacion_actividad FROM DiscussionPosts DP WHERE DP.ForumId = actividad_id AND DP.UserId = aprendizID AND IsDeleted=0 ORDER BY 1 DESC LIMIT 1;
							END;
                            
						ELSE
							SET calificacion_actividad = "0";
                        END IF;
                    END IF;
                   
                    SET @update_sql = CONCAT('UPDATE tmp_informe_cierre SET `',actividad_nombre,'` = "',calificacion_actividad,'" WHERE Aprendiz_Rut = "',username,'"');
                    
                    -- select @update_sql;
                    PREPARE stmt FROM @update_sql;
					EXECUTE stmt;
					DEALLOCATE PREPARE stmt;
				
				END LOOP loop_actividades;
				CLOSE cursor_actividades;
			END;
            
            -- Fin de loop de actividades del estudiante, agregamos el progreso y cerramos.
            UPDATE tmp_informe_cierre SET `Progreso de contenido` = CONCAT(progreso_estudiante, "/" , total_contenido) WHERE `Aprendiz_Rut` = username;
        END LOOP loop_aprendices;
        CLOSE cursor_aprendices;
    END;
    
    SELECT * FROM tmp_informe_cierre ORDER BY Aprendiz_Apellido ASC;
END
