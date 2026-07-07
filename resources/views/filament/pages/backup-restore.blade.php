<x-filament-panels::page>
    <div style="font-family: 'Outfit', 'Inter', sans-serif; padding-bottom: 50px;">
        
        <!-- Header Banner -->
        <div style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #312e81 100%); border-radius: 16px; padding: 28px; margin-bottom: 28px; position: relative; overflow: hidden; box-shadow: 0 10px 25px rgba(79, 70, 229, 0.25);">
            <div style="position: absolute; top: -20px; right: -20px; width: 140px; height: 140px; border-radius: 50%; background: rgba(255, 255, 255, 0.08); pointer-events: none;"></div>
            <div style="position: absolute; bottom: -40px; left: 20%; width: 200px; height: 200px; border-radius: 50%; background: rgba(255, 255, 255, 0.05); pointer-events: none;"></div>
            <div style="position: relative; z-index: 1;">
                <h1 style="color: #ffffff; font-size: 24px; font-weight: 700; margin: 0 0 6px 0; letter-spacing: -0.02em;">🔄 Backup & Restore Manager</h1>
                <p style="color: rgba(255, 255, 255, 0.8); font-size: 14px; margin: 0; font-weight: 400; line-height: 1.5;">
                    Intelligently backup and sync Catalog data (Products, Categories, Brands, and Variants) along with all media assets between local and production servers.
                </p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
            
            <!-- Export Card -->
            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s, box-shadow 0.2s;" 
                 onmouseenter="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 15px -3px rgba(0, 0, 0, 0.1)';" 
                 onmouseleave="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.05)';">
                <div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <div style="width: 44px; height: 44px; border-radius: 12px; background: #e0e7ff; display: flex; align-items: center; justify-content: center; color: #4f46e5;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px; height: 24px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0l-3-3m3 3l3-3m-8.25 6a9 9 0 1116.5 0" />
                            </svg>
                        </div>
                        <h2 style="font-size: 18px; font-weight: 700; color: #1f2937; margin: 0;">Export Backup</h2>
                    </div>
                    <p style="font-size: 14px; color: #6b7280; line-height: 1.6; margin: 0 0 24px 0;">
                        Generates a secure ZIP archive containing all catalog databases (Brands, Categories, Products, and Variants) in JSON format, along with all associated images (logos, icons, and thumbnails).
                    </p>
                </div>
                <button wire:click="downloadBackup" type="button" 
                        style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #ffffff; background: linear-gradient(135deg, #6366f1, #4f46e5); border: none; border-radius: 12px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);"
                        onmouseenter="this.style.opacity='0.9';" onmouseleave="this.style.opacity='1';">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Download Backup ZIP</span>
                </button>
            </div>

            <!-- Import Card -->
            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); transition: transform 0.2s, box-shadow 0.2s;"
                 onmouseenter="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 15px -3px rgba(0, 0, 0, 0.1)';" 
                 onmouseleave="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.05)';">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #10b981;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px; height: 24px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a9 9 0 1010.5 0" />
                        </svg>
                    </div>
                    <h2 style="font-size: 18px; font-weight: 700; color: #1f2937; margin: 0;">Restore Backup</h2>
                </div>
                <p style="font-size: 14px; color: #6b7280; line-height: 1.6; margin: 0 0 20px 0;">
                    Upload the exported ZIP file to restore or sync catalog data. Slugs are matched to prevent duplicate inserts and maintain active orders.
                </p>

                <!-- Drag-and-drop / File Upload area -->
                <div style="margin-bottom: 20px;">
                    <label style="cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; border: 2px dashed #d1d5db; border-radius: 12px; background: #f9fafb; transition: border-color 0.2s, background-color 0.2s;"
                           onmouseenter="this.style.borderColor='#4f46e5'; this.style.backgroundColor='#f5f3ff';"
                           onmouseleave="this.style.borderColor='#d1d5db'; this.style.backgroundColor='#f9fafb';">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 32px; height: 32px; color: #9ca3af; margin-bottom: 8px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        @if($backupFile)
                            <span style="font-size: 13px; font-weight: 600; color: #4f46e5; text-align: center;">
                                📁 {{ $backupFile->getClientOriginalName() }}
                            </span>
                            <span style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                                Ready to restore. Size: {{ round($backupFile->getSize() / 1024 / 1024, 2) }} MB
                            </span>
                        @else
                            <span style="font-size: 13px; font-weight: 500; color: #4b5563;">Click to select backup .zip file</span>
                            <span style="font-size: 11px; color: #9ca3af; margin-top: 2px;">Supported extension: ZIP only</span>
                        @endif
                        <input type="file" wire:model="backupFile" accept=".zip" style="display: none;">
                    </label>
                </div>

                <button wire:click="restoreBackup" type="button" 
                        wire:loading.attr="disabled"
                        style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #ffffff; background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 12px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25);"
                        onmouseenter="this.style.opacity='0.9';" onmouseleave="this.style.opacity='1';">
                    <span wire:loading.remove wire:target="restoreBackup">⚡ Start Restore Process</span>
                    <span wire:loading wire:target="restoreBackup" style="display: inline-flex; align-items: center; gap: 6px;">
                        <svg style="width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.4); border-top-color:#ffffff; border-radius:50%; animation: spin 0.8s linear infinite;" viewBox="0 0 24 24"></svg>
                        Restoring...
                    </span>
                </button>
            </div>
        </div>

        <!-- Error Panel -->
        @if($errorMessage)
            <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 12px; padding: 16px; margin-bottom: 24px; display: flex; gap: 12px; align-items: flex-start; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.05);">
                <div style="color: #ef4444; flex-shrink: 0; margin-top: 2px;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h4 style="font-size: 14px; font-weight: 700; color: #991b1b; margin: 0 0 4px 0;">Restore Operation Failed</h4>
                    <p style="font-size: 13px; color: #b91c1c; margin: 0; line-height: 1.5; font-family: monospace;">{{ $errorMessage }}</p>
                </div>
            </div>
        @endif

        <!-- Success Stats Panel -->
        @if($importStats)
            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); animation: fadeIn 0.4s ease;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #f3f4f6; padding-bottom: 16px;">
                    <span style="font-size: 24px;">🎉</span>
                    <div>
                        <h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0;">Backup Restored Successfully!</h3>
                        <p style="font-size: 12px; color: #6b7280; margin: 2px 0 0 0;">All entities were processed and mapped safely.</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                    <!-- Brands Card -->
                    <div style="background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 12px; padding: 16px;">
                        <span style="font-size: 12px; font-weight: 700; color: #4b5563; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 8px;">🏷️ Brands</span>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #1f2937;">
                            <span>Created:</span>
                            <span style="font-weight: 700; color: #10b981;">{{ $importStats['brands']['created'] }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #1f2937; margin-top: 4px;">
                            <span>Updated:</span>
                            <span style="font-weight: 700; color: #3b82f6;">{{ $importStats['brands']['updated'] }}</span>
                        </div>
                    </div>

                    <!-- Categories Card -->
                    <div style="background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 12px; padding: 16px;">
                        <span style="font-size: 12px; font-weight: 700; color: #4b5563; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 8px;">📁 Categories</span>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #1f2937;">
                            <span>Created:</span>
                            <span style="font-weight: 700; color: #10b981;">{{ $importStats['categories']['created'] }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #1f2937; margin-top: 4px;">
                            <span>Updated:</span>
                            <span style="font-weight: 700; color: #3b82f6;">{{ $importStats['categories']['updated'] }}</span>
                        </div>
                    </div>

                    <!-- Products Card -->
                    <div style="background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 12px; padding: 16px;">
                        <span style="font-size: 12px; font-weight: 700; color: #4b5563; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 8px;">📦 Products</span>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #1f2937;">
                            <span>Created:</span>
                            <span style="font-weight: 700; color: #10b981;">{{ $importStats['products']['created'] }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #1f2937; margin-top: 4px;">
                            <span>Updated:</span>
                            <span style="font-weight: 700; color: #3b82f6;">{{ $importStats['products']['updated'] }}</span>
                        </div>
                    </div>

                    <!-- Variants Card -->
                    <div style="background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 12px; padding: 16px;">
                        <span style="font-size: 12px; font-weight: 700; color: #4b5563; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 8px;">⚙️ Variants</span>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #1f2937;">
                            <span>Created:</span>
                            <span style="font-weight: 700; color: #10b981;">{{ $importStats['variants']['created'] }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #1f2937; margin-top: 4px;">
                            <span>Updated:</span>
                            <span style="font-weight: 700; color: #3b82f6;">{{ $importStats['variants']['updated'] }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #1f2937; margin-top: 4px;">
                            <span>Deleted:</span>
                            <span style="font-weight: 700; color: #ef4444;">{{ $importStats['variants']['deleted'] }}</span>
                        </div>
                    </div>

                    <!-- Images Card -->
                    <div style="background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 12px; padding: 16px;">
                        <span style="font-size: 12px; font-weight: 700; color: #4b5563; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 8px;">🖼️ Images</span>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #1f2937;">
                            <span>Copied:</span>
                            <span style="font-weight: 700; color: #8b5cf6;">{{ $importStats['images_restored'] }}</span>
                        </div>
                        <div style="font-size: 11px; color: #9ca3af; margin-top: 12px;">
                            Images are copied to storage/disk.
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
    </div>

    <!-- Spin animation keyframes -->
    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</x-filament-panels::page>
