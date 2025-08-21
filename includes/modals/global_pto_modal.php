<div class="modal fade" id="addGlobalPtoModal" tabindex="-1" aria-labelledby="addGlobalPtoModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <form id="addGlobalPTOForm">
                        <div class="modal-header">
                          <h5 class="modal-title" id="addGlobalPtoModalLabel">Add Global PTO</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">

                            <!-- PTO Note -->
                            <div class="mb-3">
                                <label for="pto_note" class="form-label">Time Off Note</label>
                                <input type="text" class="form-control" id="pto_note" name="timeoff_note" placeholder="e.g., Labor Day" required>
                            </div>

                            <!-- Month Selectors -->
                            <div class="mb-3 d-flex gap-2">
                                <div>
                                    <label for="startMonth" class="form-label">Start Month</label>
                                    <select id="startMonth" class="form-select" required>
                                        <option value="">-- Select Month --</option>
                                        <option value="1">January</option>
                                        <option value="2">February</option>
                                        <option value="3">March</option>
                                        <option value="4">April</option>
                                        <option value="5">May</option>
                                        <option value="6">June</option>
                                        <option value="7">July</option>
                                        <option value="8">August</option>
                                        <option value="9">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12">December</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="endMonth" class="form-label">End Month</label>
                                    <select id="endMonth" class="form-select" required>
                                        <option value="">-- Select Month --</option>
                                        <option value="1">January</option>
                                        <option value="2">February</option>
                                        <option value="3">March</option>
                                        <option value="4">April</option>
                                        <option value="5">May</option>
                                        <option value="6">June</option>
                                        <option value="7">July</option>
                                        <option value="8">August</option>
                                        <option value="9">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12">December</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Week Selectors -->
                            <div class="mb-3" id="weekSelectorContainer" style="display:none;">
                                <label class="form-label">Select Start and End Week</label>
                                <div class="d-flex gap-2">
                                    <select id="startWeek" class="form-select" required>
                                        <option value="">Start Week</option>
                                    </select>
                                    <select id="endWeek" class="form-select" required>
                                        <option value="">End Week</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Day Inputs -->
                            <div id="dayHoursContainer" class="flex-column" style="display:none; margin-top:1rem;">
                                <p><strong>Enter Hours per Day:</strong></p>
                                <div id="dayInputs" class="d-flex flex-column gap-2"></div>

                                <!-- Summary -->
                                <div id="summaryFooter">
                                  <span id="summaryText">Total Weeks: 0 | Total Hours: 0</span>
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer">
                          <button type="submit" class="btn btn-primary">Add Global PTO</button>
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>