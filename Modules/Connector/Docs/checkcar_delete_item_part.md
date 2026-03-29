# CheckCar API: `deleteItemPart`

Endpoint to delete or partially clear a single inspection element (options and/or media) for a specific inspection.

## Endpoint

- **Method:** `POST`
- **URL:** `/api/checkcar/inspections/{inspection_id}/delete-item-part`
- **Auth:** Bearer token (same as other Connector APIs)

## Request Fields

- **`element_id`** (integer, required)
  - ID of the `checkcar_elements` record associated with the inspection item.
- **`delete_element`** (boolean, optional)
  - If `true`, deletes the entire inspection item (options + media) for that element.
- **`clear_options`** (boolean, optional)
  - If `true`, clears all selected options for this element (`option_ids` becomes an empty array).
- **`clear_images`** (boolean, optional)
  - If `true`, clears all media (images, videos, PDFs) stored in `images` for this element.
- **`option_ids`** (array of integers, optional)
  - When provided, these option IDs are removed from the current `option_ids` list; other options are kept.
- **`image_file_paths`** (array of strings, optional)
  - List of `file_path` values to remove from `images`. Paths must match the values returned from `getByJobSheet` / `update`.

> Note: `delete_element`, `clear_options`, `clear_images`, `option_ids`, and `image_file_paths` are mutually combinable depending on what you need to change. `element_id` is always required.

---

## Examples

### 1. Delete the whole element (options + media)

Removes the entire `CheckCarInspectionItem` row for the given `element_id`.

```http
POST /api/checkcar/inspections/15/delete-item-part
Content-Type: application/json
Authorization: Bearer <token>
```

```json
{
  "element_id": 42,
  "delete_element": true
}
```

**Effect:**
- The inspection item for `element_id = 42` is deleted.
- All options and media (images/videos/PDFs) for that element are removed.

---

### 2. Clear all options but keep media

```json
{
  "element_id": 42,
  "clear_options": true
}
```

**Effect:**
- `option_ids` becomes `[]` for this element.
- `images` (all media) remains unchanged.

---

### 3. Remove only some options

```json
{
  "element_id": 42,
  "option_ids": [3, 5, 7]
}
```

**Effect:**
- Only options with IDs `3`, `5`, `7` are removed from `option_ids`.
- Any other selected options remain.
- Media is not affected.

---

### 4. Clear all media (images/videos/PDFs) for the element

```json
{
  "element_id": 42,
  "clear_images": true
}
```

**Effect:**
- `images` becomes `[]` for this element.
- Options (`option_ids`) remain unchanged.

---

### 5. Remove specific media files only

You pass the exact `file_path` values you want to delete. These paths are the same ones returned by `getByJobSheet` or `update` in the `images[*].file_path` fields.

```json
{
  "element_id": 42,
  "image_file_paths": [
    "checkcar/inspections/15/inspection_15_element_photo_1700000000_xxx.jpg",
    "checkcar/inspections/15/inspection_15_element_video_1700000001_yyy.mp4"
  ]
}
```

**Effect:**
- Only media entries whose `file_path` matches one of the provided strings are removed from `images`.
- All other media entries remain.
- Options (`option_ids`) remain unchanged.

---

## Notes

- **`element_id` is always required.** The API will return a validation error if it is missing or invalid.
- If `delete_element` is `true`, other flags are ignored (the whole item is deleted).
- The API works with any media types stored in `images` (images, videos, PDFs). The delete logic is based purely on `file_path` values.
