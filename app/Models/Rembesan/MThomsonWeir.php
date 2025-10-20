<?php
namespace App\Models\Rembesan;

use CodeIgniter\Model;
use CodeIgniter\Events\Events;

class MThomsonWeir extends Model
{
    protected $table = 't_thomson_weir';
    protected $primaryKey = 'id';
    protected $allowedFields = ['pengukuran_id', 'a1_r', 'a1_l', 'b1', 'b3', 'b5'];
    
    protected $validationRules = [
        'pengukuran_id' => 'required|numeric|is_not_unique[t_data_pengukuran.id]',
        'a1_r' => 'permit_empty|numeric',
        'a1_l' => 'permit_empty|numeric',
        'b1' => 'permit_empty|numeric',
        'b3' => 'permit_empty|numeric',
        'b5' => 'permit_empty|numeric'
    ];
    
    protected $validationMessages = [
        'pengukuran_id' => [
            'required' => 'pengukuran_id harus diisi',
            'numeric' => 'pengukuran_id harus berupa angka',
            'is_not_unique' => 'Data pengukuran dengan ID {value} tidak ditemukan'
        ]
    ];

    /**
     * Simpan atau update data Thomson Weir
     */
    public function simpanThomson(array $data, int $pengukuran_id = null): array
    {
        $db = \Config\Database::connect();
        $get = fn($key) => (isset($data[$key]) && trim($data[$key]) !== '') ? $data[$key] : null;

        try {
            if (!$pengukuran_id) {
                return ['status' => 'error', 'message' => 'Silakan pilih data pengukuran terlebih dahulu!'];
            }

            $existing = $this->where('pengukuran_id', $pengukuran_id)->first();
            $fields = ['a1_r', 'a1_l', 'b1', 'b3', 'b5'];

            // 🧩 Jika sudah ada data → update kolom kosong saja
            if ($existing) {
                $updateData = [];
                $infoMessages = [];

                foreach ($fields as $field) {
                    $newValue = $get($field);
                    if ($newValue !== null) {
                        if ($existing[$field] === null) {
                            $updateData[$field] = $newValue;
                        } else {
                            $infoMessages[] = "Kolom {$field} sudah terisi, tidak diperbarui.";
                        }
                    }
                }

                if (!empty($updateData)) {
                    $db->transStart();
                    if (!$this->update($existing['id'], $updateData)) {
                        $db->transRollback();
                        return [
                            'status' => 'error',
                            'message' => 'Gagal memperbarui data Thomson Weir: ' .
                                         implode(', ', $this->errors())
                        ];
                    }
                    $db->transComplete();
                    return [
                        'status' => 'success',
                        'message' => 'Sebagian data Thomson berhasil diperbarui.' .
                                     (!empty($infoMessages) ? ' ' . implode(' ', $infoMessages) : '')
                    ];
                }

                return [
                    'status' => 'info',
                    'message' => 'Tidak ada kolom yang diperbarui. ' .
                                 (!empty($infoMessages) ? implode(' ', $infoMessages) : 'Data Thomson sudah lengkap.')
                ];
            }

            // 🆕 Jika belum ada data → insert baru
            $insertData = ['pengukuran_id' => $pengukuran_id];
            $hasValue = false;

            foreach ($fields as $field) {
                $val = $get($field);
                $insertData[$field] = $val;
                if ($val !== null) {
                    $hasValue = true;
                }
            }

            if (!$hasValue) {
                return ['status' => 'error', 'message' => 'Minimal satu nilai Thomson Weir harus diisi!'];
            }

            $db->transStart();
            if (!$this->insert($insertData)) {
                $db->transRollback();
                return [
                    'status' => 'error',
                    'message' => 'Gagal menyimpan data Thomson Weir: ' .
                                 implode(', ', $this->errors())
                ];
            }

            Events::trigger('dataThomson:insert', $pengukuran_id);
            $db->transComplete();

            return ['status' => 'success', 'message' => 'Data Thomson Weir berhasil disimpan.'];
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[MThomsonWeir::simpanThomson] ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }
}
