# REGLAS CRÍTICAS DE DESARROLLO (LEER OBLIGATORIAMENTE ANTES DE CADA TAREA)

1. PROHIBICIÓN EN AUTENTICACIÓN Y BASE DE DATOS:
   - NUNCA ejecutes `migrate:fresh`, `db:drop`, `truncate` ni scripts que limpien la base de datos sin autorización explícita.
   - NUNCA alteres la lógica de hash/encriptación de contraseñas (`bcrypt`, `Hash::make`, `password_hash`, etc.).
   - NUNCA modifiques seeders ni usuarios existentes en la tabla `users`.

2. RUTAS, ROLES Y CONTROLADORES:
   - NUNCA toques ni reescribas los archivos de rutas centrales (`routes/web.*`, `routes/api.*`, `App.tsx`, router principal) a menos que la tarea lo solicite explícitamente.
   - NUNCA elimines ni modifiques middlewares de autenticación o verificación de roles/permisos.
   - Si una tarea es de frontend o estilos, QUEDA ESTRICTAMENTE PROHIBIDO modificar controladores, migraciones, modelos o configuraciones de autenticación.

3. ALCANCE ATÓMICO:
   - Solo puedes editar los archivos específicamente solicitados en la tarea.
   - No apliques formateos masivos ni refactorizaciones no solicitadas en archivos existentes.
