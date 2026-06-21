What I need to build

This task is asking me to build a Laravel JSON API that lets authenticated clients track physical progress over time.

The two main features:

    Measurements

            - Clients can log body measurements for a specific calendar date.
            - A client can only have one measurement record per day.
            - Clients can list, update, and delete their own measurements.
            - Measurements can be filtered by a start and end date.

    Progress photos

            - Clients can upload progress photos.
            - The uploaded photo file is stored on the local disk.
            - The database stores metadata about the photo, including the generated storage path.
            - Clients can list their own photo metadata.
            - Clients can delete a photo, which must delete both the database record and the stored file.

Coaches can view a client’s measurement history and progress photo metadata through coach-only endpoints.

All endpoints require Sanctum authentication.

--------------------------------------------------------------------------------------------------------------------------------------------

What inputs it takes


    POST /api/measurements

        Creates a measurement for the authenticated user.

            Expected input:

                ```json
                {
                "measured_at": "2026-06-15",
                "weight": 82.50,
                "body_fat_percentage": 18.4,
                "notes": "Felt good today",
                "unit_system": "metric"
                }
                ```

        Rules I understood from the brief:

            measured_at is required and must be a date.
            unit_system is required and must be either metric or imperial.
            weight is optional.
            body_fat_percentage is optional.
            notes is optional.
            The same user cannot create two measurements with the same measured_at date.

        Returns:

            The created measurement record.
            If the user already has a measurement for that date, return a 422 validation error.

--------------------------------------------------------------------

    GET /api/measurements

        Lists the authenticated user’s own measurements.

    Optional query filters:

            ?start_date=2026-01-01&end_date=2026-01-31

    Expected behavior:

        - Without filters, return the user’s own measurements.
        - With start_date, only return measurements on or after that date.
        - With end_date, only return measurements on or before that date.
        - With both filters, return measurements inside that date range only.

--------------------------------------------------------------------

    PUT /api/measurements/{id}

        Updates one of the authenticated user’s own measurements.

            Possible input:

                ```json
                {
                "measured_at": "2026-06-16",
                "weight": 83.10,
                "body_fat_percentage": 18.1,
                "notes": "Updated notes",
                "unit_system": "metric"
                }
                ```

    Expected behavior:

        - A client can only update their own measurement.
        - Updating the date must still respect the one-measurement-per-user-per-day rule.
        - If the update would create a duplicate date for the same user, return 422.

--------------------------------------------------------------------

    DELETE /api/measurements/{id}

        Deletes one of the authenticated user’s own measurements.

    Expected behavior:

        - A client can only delete their own measurement.
        - Another user’s measurement should return 403 or otherwise be blocked by authorization.

--------------------------------------------------------------------

    POST /api/photos

        Uploads a progress photo for the authenticated user.

    Expected input:

        photo: uploaded image file
        taken_at: 2026-06-15
        caption: Optional caption

    Rules:

        - The file must be JPEG, PNG, or WEBP.
        - The maximum file size is 5 MB.
        - Validation must be based on MIME type, not just the file extension.
        - The system must generate the storage path.
        - The client’s original filename must not be used in the stored path.
        - The file should be stored in storage/app/progress-photos/.

    Returns:

        - The created photo metadata record.
        - No binary image data is returned.

--------------------------------------------------------------------

    GET /api/photos

        Lists the authenticated user’s own progress photo records.

        Expected return:

            - Metadata only.
            - Should include fields like id, user_id, taken_at, storage_path, caption, created_at, and updated_at.
            - Should not return the binary image file.

--------------------------------------------------------------------

    DELETE /api/photos/{id}

        Deletes one of the authenticated user’s own progress photos.

    Expected behavior:

            1. Find the photo record.
            2. Confirm the authenticated user owns it.
            3. Delete the stored file from disk.
            4. Delete the database record.
            5. After the request completes:
                - The file should no longer exist in storage/app/progress-photos/.
                - The database row should no longer exist.
                - There should be no orphaned file left behind.

--------------------------------------------------------------------

    GET /api/coach/clients/{user}/measurements

        Coach-only endpoint.

    Expected behavior:

        - Authenticated user must have role = coach.
        - Non-coaches receive 403.
        - Returns the selected client’s measurements.

--------------------------------------------------------------------

    GET /api/coach/clients/{user}/photos

        Coach-only endpoint.

    Expected behavior:

        - Authenticated user must have role = coach.
        - Non-coaches receive 403.
        - Returns the selected client’s progress photo metadata only.
        - Does not return binary image data.

--------------------------------------------------------------------------------------------------------------------------------------------

What the API returns

    This is a JSON API, so it does not display HTML pages.

        JSON responses for:

            - Created measurements.
            - Updated measurements.
            - Lists of measurements.
            - Created photo metadata.
            - Lists of photo metadata.
            - Deletion success responses.
            - Validation errors using 422.
            - Authorization errors using 403.

--------------------------------------------------------------------------------------------------------------------------------------------

What to do about the “any common image format” that was in the brief?


Theres a contradiction in the brief since it says can use any image format but in the acceptance criteria it specificly points to using JPEG, PNG, and WEBP and all other formats like GIF, HEIC, BMP, and TIFF should fail validation with a 422 response.

So to follow the acceptance criteria we will only accept JPEG's, PNG's, and WEBP's and will fail every other format

--------------------------------------------------------------------------------------------------------------------------------------------

Coach-client relationship is not fully defined

I will only enforce the explicit requirement that coach endpoints require role = coach, to access client information

if required to later i can set up an extra coach-client relationship table

--------------------------------------------------------------------------------------------------------------------------------------------

The users table needs a role column

    So I need to add a migration to add a role column to the users table.

        - Users can have roles like client and coach.
        - The default role should probably be client.
        - Coach-only endpoints check that the authenticated user’s role is coach.

--------------------------------------------------------------------------------------------------------------------------------------------

Measurement ownership must be enforced

    Clients may only access their own records.

    This means every measurement and progress photo must be scoped to auth()->id() for normal client endpoints.

        A client should not be able to:

            - View another client’s measurements.
            - Update another client’s measurements.
            - Delete another client’s measurements.
            - View another client’s photo metadata.
            - Delete another client’s photos.

--------------------------------------------------------------------------------------------------------------------------------------------

Photo ownership must be enforced

    The same ownership rule applies to progress photos.

    A client should not be able to delete another user’s progress photo record or file.

--------------------------------------------------------------------------------------------------------------------------------------------

MIME type validation matters

    The acceptance criteria say validation must be based on MIME type, not file extension.

    This matters because a file extension can be faked.

    could actually contain a PDF, script, or other invalid file content.

    I should use Laravel validation rules that check the uploaded file’s detected MIME type, such as:

        mimes:jpg,jpeg,png,webp

    or a MIME-based validation rule like:

        mimetypes:image/jpeg,image/png,image/webp

--------------------------------------------------------------------------------------------------------------------------------------------

Original filenames should not be used in stored paths

    The stored path must be generated by the system.

    This means the client’s original filename should not be trusted or used as the final stored filename.

        Reasons:
            - Original filenames can contain unsafe or strange characters.
            - Different users may upload files with the same name.
            - A filename may reveal private information.
            - A malicious filename could cause path or storage issues if handled badly.

    I will generate a safe filename, likely using a UUID, and store the file under the progress-photos/ directory.

--------------------------------------------------------------------------------------------------------------------------------------------

Exact file delete sequence needs to be clear

    When DELETE /api/photos/{id} is called, the sequence should be:

        1. Find the progress photo record.
        2. Check that the authenticated user owns the record.
        3. Check if the file exists on disk.
        4. Delete the file from disk.
        5. Delete the database record.
        6. Return a success response.

    After the call completes:
        
        1. The database record should be gone.
        2. The file should be gone from local storage.
        3. A filesystem check should show that the file no longer exists.

    One thing to be careful about is partial failure. If the database row is deleted but the file is not deleted, the app leaves an orphaned file behind.

--------------------------------------------------------------------------------------------------------------------------------------------

One measurement per day needs a database constraint

    A client should only have one measurement record per calendar day.

        This should not only be handled in validation. It should also be enforced at the database level.

        Migration should include a unique constraint on:

        user_id + measured_at

            Exact constraint:

            $table->unique(['user_id', 'measured_at']);

        This means one user cannot have two measurements with the same measured_at date.
        Different users can still have measurements on the same date.

--------------------------------------------------------------------------------------------------------------------------------------------

Updating a measurement date could create a duplicate

    The duplicate measurement rule applies to both create and update

    - A client already has a measurement for 2026-06-15.
    - The same client also has a measurement for 2026-06-16.
    - They try to update the June 16 record and change measured_at to 2026-06-15.

    That should fail with a 422 validation error because it would create two records for the same user on the same date.

--------------------------------------------------------------------------------------------------------------------------------------------

Unit system is stored as provided, not converted

    unit_system must be metric or imperial; measurements are stored as provided (no conversion)

        If a client submits:

            ```json
            {
            "weight": 185,
            "unit_system": "imperial"
            }
            ```

        It does not convert 185 lbs into kilograms.

        Those numbers cannot be directly compared as the same unit. The API stores the values as entered and leaves interpretation or conversion to the caller.

--------------------------------------------------------------------------------------------------------------------------------------------

Measurement fields are optional, but the minimum useful payload is unclear

    The table says weight, body_fat_percentage, and notes are nullable.

    allow nullable values as the schema says, unless later tests require at least one of weight, body_fat_percentage, or notes

--------------------------------------------------------------------------------------------------------------------------------------------

Date validation range is not specified

    validate these as dates, but do not block future dates.

--------------------------------------------------------------------------------------------------------------------------------------------

Photo caption length is specified

    The progress_photos.caption column is string(200) and nullable.

    So validation should enforce:

        nullable|string|max:200

--------------------------------------------------------------------------------------------------------------------------------------------

Photo binary access is not required

    There is no endpoint like:

        GET /api/photos/{id}/file

    So I should not build binary image download or viewing endpoints unless later requested.

--------------------------------------------------------------------------------------------------------------------------------------------
